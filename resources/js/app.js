import './bootstrap';
import '../sass/app.scss';

const attendanceScanner = document.querySelector('[data-attendance-scanner]');
if (attendanceScanner) {
    import('./school-attendance-scanner').then(({ mountAttendanceScanner }) => mountAttendanceScanner(attendanceScanner));
}
