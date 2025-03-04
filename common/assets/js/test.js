/**
	 * Адаптация при ресайзе окна (восстановлена логика для <= 767)
	 */
function windowResizeHover() {
    var windowSize = document.documentElement.clientWidth;
    if (windowSize < 1025 && windowSize > 767) {
        document.body.classList.remove("twocolumn-panel");
        if (hasNavbarMenu) {
            document.documentElement.setAttribute("data-sidebar-size", "sm");
            if (document.querySelector(".hamburger-icon")) document.querySelector(".hamburger-icon").classList.add("open");
        }
    } else if (windowSize >= 1025) {
        document.body.classList.remove("twocolumn-panel");
        if (hasNavbarMenu) {
            document.documentElement.setAttribute("data-sidebar-size", sessionStorage.getItem("data-sidebar-size") || "lg");
            if (document.querySelector(".hamburger-icon")) document.querySelector(".hamburger-icon").classList.remove("open");
        }
    } else if (windowSize <= 767 && hasNavbarMenu) {
        document.body.classList.remove("vertical-sidebar-enable");
        document.body.classList.add("twocolumn-panel");
        document.documentElement.setAttribute("data-sidebar-size", "lg");
        if (document.querySelector(".hamburger-icon")) document.querySelector(".hamburger-icon").classList.add("open");
    }
}