@if($paginator->hasPages())
    <div class="container-xxl bg-white shadow">
        <div class="row justify-content-center py-2 @if(!empty($isTop)) mb-3 @endif shadow">
            <div class="col-lg-12 d-flex justify-content-center">
                {{ $paginator->links() }}
            </div>
        </div>
    </div>
@endif
