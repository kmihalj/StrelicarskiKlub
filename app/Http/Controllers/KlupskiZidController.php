<?php

namespace App\Http\Controllers;

use App\Models\Clanovi;
use App\Models\ClubWallMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Kontroler za javni feed poruka "Klupski zid" na naslovnici.
 */
class KlupskiZidController extends Controller
{
    private const MESSAGE_LIMIT = 25;
    private const MAX_MESSAGE_LENGTH = 1000;
    private ?bool $clubWallTableSupported = null;

    /**
     * Vraća zadnjih 25 poruka za prikaz na naslovnici (AJAX).
     */
    public function index(Request $request): JsonResponse
    {
        if (!$this->supportsClubWall()) {
            return response()->json([
                'messages' => [],
                'signature' => 'disabled',
                'canPost' => false,
                'canModerate' => false,
                'serverTime' => now()->toIso8601String(),
                'disabled' => true,
                'disabledReason' => 'Klupski zid trenutno nije dostupan. Pokrenite migracije.',
            ]);
        }

        $messages = $this->latestMessages();

        return response()->json([
            'messages' => $this->serializeMessages($messages),
            'signature' => $this->messagesSignature($messages),
            'canPost' => $this->userCanPost($request->user()),
            'canModerate' => $this->userCanModerate($request->user()),
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    /**
     * Sprema novu poruku klupskog zida.
     *
     * Dopušteno samo korisnicima koji imaju pravo admin/member/school ili roditeljsko pravo.
     */
    public function store(Request $request): JsonResponse
    {
        if (!$this->supportsClubWall()) {
            return response()->json([
                'message' => 'Klupski zid trenutno nije dostupan. Pokrenite migracije.',
            ], 503);
        }

        $user = $request->user();
        if (!$this->userCanPost($user)) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:' . self::MAX_MESSAGE_LENGTH],
        ], [
            'message.required' => 'Unesite poruku.',
            'message.max' => 'Poruka može imati najviše ' . self::MAX_MESSAGE_LENGTH . ' znakova.',
        ]);

        $messageText = $this->normalizeMessage((string)$validated['message']);
        if ($messageText === '') {
            throw ValidationException::withMessages([
                'message' => 'Poruka ne može biti prazna.',
            ]);
        }

        $author = $this->resolveAuthorSnapshot($user);

        ClubWallMessage::query()->create([
            'user_id' => (int)$user->id,
            'author_clan_id' => $author['clan_id'],
            'author_name' => $author['name'],
            'message' => $messageText,
            'is_highlighted' => false,
        ]);

        $messages = $this->latestMessages();

        return response()->json([
            'message' => 'Poruka je spremljena.',
            'messages' => $this->serializeMessages($messages),
            'signature' => $this->messagesSignature($messages),
            'canPost' => true,
            'canModerate' => $this->userCanModerate($user),
        ]);
    }

    /**
     * Soft-delete poruke (admin).
     */
    public function destroy(Request $request, ClubWallMessage $message): JsonResponse
    {
        if (!$this->supportsClubWall()) {
            return response()->json([
                'message' => 'Klupski zid trenutno nije dostupan.',
            ], 503);
        }

        $user = $request->user();
        if (!$this->userCanModerate($user)) {
            abort(403);
        }

        $message->deleted_by_user_id = (int)$user->id;
        $message->save();
        $message->delete();

        $messages = $this->latestMessages();

        return response()->json([
            'message' => 'Poruka je obrisana.',
            'messages' => $this->serializeMessages($messages),
            'signature' => $this->messagesSignature($messages),
            'canPost' => $this->userCanPost($user),
            'canModerate' => true,
        ]);
    }

    /**
     * Uključuje/isključuje isticanje poruke (admin).
     */
    public function toggleHighlight(Request $request, ClubWallMessage $message): JsonResponse
    {
        if (!$this->supportsClubWall()) {
            return response()->json([
                'message' => 'Klupski zid trenutno nije dostupan.',
            ], 503);
        }

        $user = $request->user();
        if (!$this->userCanModerate($user)) {
            abort(403);
        }

        $message->is_highlighted = !$message->is_highlighted;
        $message->highlighted_by_user_id = $message->is_highlighted ? (int)$user->id : null;
        $message->save();

        $messages = $this->latestMessages();

        return response()->json([
            'message' => $message->is_highlighted
                ? 'Poruka je istaknuta.'
                : 'Isticanje je uklonjeno.',
            'messages' => $this->serializeMessages($messages),
            'signature' => $this->messagesSignature($messages),
            'canPost' => $this->userCanPost($user),
            'canModerate' => true,
        ]);
    }

    /**
     * Čisti kontrolne znakove i normalizira newline format poruke.
     */
    private function normalizeMessage(string $message): string
    {
        $normalized = str_replace(["\r\n", "\r", "\n"], ' ', $message);
        $normalized = preg_replace('/[^\P{C}\t]/u', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    /**
     * Dohvaća zadnjih N poruka koje nisu obrisane.
     */
    private function latestMessages(): Collection
    {
        return ClubWallMessage::query()
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::MESSAGE_LIMIT)
            ->get([
                'id',
                'author_clan_id',
                'author_name',
                'message',
                'is_highlighted',
                'created_at',
                'updated_at',
            ]);
    }

    /**
     * Pretvara modele poruka u payload siguran za prikaz.
     */
    private function serializeMessages(Collection $messages): array
    {
        return $messages
            ->map(function (ClubWallMessage $message): array {
                $profileUrl = null;
                if (!empty($message->author_clan_id)) {
                    $profileUrl = route('javno.clanovi.prikaz_clana', (int)$message->author_clan_id);
                }

                return [
                    'id' => (int)$message->id,
                    'authorName' => trim((string)$message->author_name),
                    'authorProfileUrl' => $profileUrl,
                    'text' => (string)$message->message,
                    'highlighted' => (bool)$message->is_highlighted,
                    'createdAt' => $message->created_at?->format('d.m.Y. H:i'),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Jednostavan potpis feeda radi izbjegavanja nepotrebnog rerendera.
     */
    private function messagesSignature(Collection $messages): string
    {
        $signatureInput = $messages
            ->map(static function (ClubWallMessage $message): string {
                $updated = $message->updated_at?->getTimestamp() ?? 0;

                return implode('|', [
                    (int)$message->id,
                    (int)$message->is_highlighted,
                    $updated,
                ]);
            })
            ->implode(';');

        return sha1($signatureInput);
    }

    /**
     * Snapshot autora poruke (ime + opcionalni clan_id za link na javni profil).
     */
    private function resolveAuthorSnapshot(User $user): array
    {
        $user->loadMissing(['clan:id,Ime,Prezime']);

        $authorClanId = null;
        $authorName = trim((string)$user->name);

        if ($user->clan instanceof Clanovi) {
            $authorClanId = (int)$user->clan->id;
            $authorName = trim((string)$user->clan->Ime . ' ' . (string)$user->clan->Prezime);
        }

        if ($authorName === '') {
            $authorName = 'Korisnik #' . (int)$user->id;
        }

        return [
            'clan_id' => $authorClanId,
            'name' => $authorName,
        ];
    }

    /**
     * Pravilo: pisanje je dopušteno samo aktivnim ulogama (admin/member/school/roditelj).
     */
    private function userCanPost(?User $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        return $user->imaPravoAdminMemberOrSchool();
    }

    /**
     * Pravilo: moderiranje poruka je dopušteno samo administratoru.
     */
    private function userCanModerate(?User $user): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        return (int)$user->rola === 1;
    }

    /**
     * Provjerava postoji li tablica poruka klupskog zida.
     */
    private function supportsClubWall(): bool
    {
        if ($this->clubWallTableSupported !== null) {
            return $this->clubWallTableSupported;
        }

        $this->clubWallTableSupported = Schema::hasTable('club_wall_messages');

        return $this->clubWallTableSupported;
    }
}
