(function () {
	"use strict";

	if (sessionStorage.getItem("data-sidebar-size")) {
		document.documentElement.setAttribute("data-sidebar-size", sessionStorage.getItem("data-sidebar-size"));
	}

	if (sessionStorage.getItem("data-bs-theme")) {
		document.documentElement.setAttribute("data-bs-theme", sessionStorage.getItem("data-bs-theme"));
	}
})();
