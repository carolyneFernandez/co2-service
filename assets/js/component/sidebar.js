$(document).ready(function () {

    let collapse = false;

    const $sideBar = $('#sidebar');

    $('#sidebarCollapse').on('click', function () {
        collapse = true;
        $sideBar.toggleClass('active');
        $sideBar.removeClass('linked');
        $(this).toggleClass('active');
    });

    $('#sidebar .nav-link:not(.dropdown-toggle), #sidebar .dropdown-item').on('click', function () {

        if (collapse) {
            $sideBar.addClass('linked');
            $sideBar.toggleClass('active');
            $('#sidebarCollapse').toggleClass('active');
        }
    });
});