(function () {
	"use strict";

	var navbarMenuHTML = document.querySelector(".navbar-menu") ? document.querySelector(".navbar-menu").innerHTML : "";
	var hasNavbarMenu = !!document.querySelector(".navbar-menu");

	/**
	 * Управление плагинами
	 */
	function pluginData() {
		var flatpickrExamples = document.querySelectorAll("[data-provider]");
		Array.from(flatpickrExamples).forEach(function (item) {
			if (item.getAttribute("data-provider") == "flatpickr") {
				var dateData = { disableMobile: "true" };
				var isFlatpickerVal = item.attributes;
				if (isFlatpickerVal["data-date-format"]) dateData.dateFormat = isFlatpickerVal["data-date-format"].value.toString();
				if (isFlatpickerVal["data-enable-time"]) {
					dateData.enableTime = true;
					dateData.dateFormat = isFlatpickerVal["data-date-format"].value.toString() + " H:i";
				}
				if (isFlatpickerVal["data-altFormat"]) {
					dateData.altInput = true;
					dateData.altFormat = isFlatpickerVal["data-altFormat"].value.toString();
				}
				if (isFlatpickerVal["data-minDate"]) dateData.minDate = isFlatpickerVal["data-minDate"].value.toString();
				if (isFlatpickerVal["data-maxDate"]) dateData.maxDate = isFlatpickerVal["data-maxDate"].value.toString();
				if (isFlatpickerVal["data-deafult-date"]) dateData.defaultDate = isFlatpickerVal["data-deafult-date"].value.toString();
				if (isFlatpickerVal["data-multiple-date"]) dateData.mode = "multiple";
				if (isFlatpickerVal["data-range-date"]) dateData.mode = "range";
				if (isFlatpickerVal["data-inline-date"]) {
					dateData.inline = true;
					dateData.defaultDate = isFlatpickerVal["data-deafult-date"].value.toString();
				}
				flatpickr(item, dateData);
			}
		});

		Array.from(document.querySelectorAll('.dropdown-menu a[data-bs-toggle="tab"]')).forEach(function (element) {
			element.addEventListener("click", function (e) {
				e.stopPropagation();
				bootstrap.Tab.getInstance(e.target).show();
			});
		});
	}

	/**
	 * Сворачивание меню
	 */
	function isCollapseMenu() {
		if (document.querySelectorAll(".navbar-nav .collapse")) {
			var collapses = document.querySelectorAll(".navbar-nav .collapse");
			Array.from(collapses).forEach(function (collapse) {
				var collapseInstance = new bootstrap.Collapse(collapse, { toggle: false });
				collapse.addEventListener("show.bs.collapse", function (e) {
					e.stopPropagation();
					var closestCollapse = collapse.parentElement.closest(".collapse");
					if (closestCollapse) {
						var siblingCollapses = closestCollapse.querySelectorAll(".collapse");
						Array.from(siblingCollapses).forEach(function (siblingCollapse) {
							var siblingCollapseInstance = bootstrap.Collapse.getInstance(siblingCollapse);
							if (siblingCollapseInstance === collapseInstance) return;
							siblingCollapseInstance.hide();
						});
					}
				});
				collapse.addEventListener("hide.bs.collapse", function (e) {
					e.stopPropagation();
					var childCollapses = collapse.querySelectorAll(".collapse");
					Array.from(childCollapses).forEach(function (childCollapse) {
						bootstrap.Collapse.getInstance(childCollapse).hide();
					});
				});
			});
		}
	}

	/**
	 * Проверка видимости элемента
	 */
	function elementInViewport(el) {
		if (el) {
			var top = el.offsetTop, left = el.offsetLeft, width = el.offsetWidth, height = el.offsetHeight;
			while (el.offsetParent) {
				el = el.offsetParent;
				top += el.offsetTop;
				left += el.offsetLeft;
			}
			return (
				top >= window.pageYOffset &&
				left >= window.pageXOffset &&
				top + height <= window.pageYOffset + window.innerHeight &&
				left + width <= window.pageXOffset + window.innerWidth
			);
		}
	}

	/**
	 * Инициализация бокового меню для semibox
	 */
	function initLeftMenuCollapse() {
		if (document.documentElement.getAttribute("data-layout") == "semibox" && hasNavbarMenu) {
			if (document.querySelector(".navbar-menu")) {
				document.querySelector(".navbar-menu").innerHTML = navbarMenuHTML;
				document.getElementById("scrollbar").setAttribute("data-simplebar", "");
				document.getElementById("navbar-nav").setAttribute("data-simplebar", "");
				document.getElementById("scrollbar").classList.add("h-100");
			}
		}
	}

	/**
	 * Закрытие боковой панели через оверлей
	 */
	function isLoadBodyElement() {
		var verticalOverlay = document.getElementsByClassName("vertical-overlay");
		if (verticalOverlay) {
			Array.from(verticalOverlay).forEach(function (element) {
				element.addEventListener("click", function () {
					document.body.classList.remove("vertical-sidebar-enable");
					document.documentElement.setAttribute("data-sidebar-size", sessionStorage.getItem("data-sidebar-size") || "lg");
				});
			});
		}
	}

	/**
	 * Адаптация при ресайзе окна
	 */
	function windowResizeHover() {
		var windowSize = document.documentElement.clientWidth;
		if (windowSize < 1025 && windowSize > 767) {
			document.body.classList.remove("twocolumn-panel");
			if (hasNavbarMenu) {
				document.documentElement.setAttribute("data-sidebar-size", "sm-hover");
				if (document.querySelector(".hamburger-icon")) document.querySelector(".hamburger-icon").classList.add("open");
			}
		} else if (windowSize <= 767 && hasNavbarMenu) {
			document.body.classList.remove("vertical-sidebar-enable");
			document.body.classList.add("twocolumn-panel");
			document.documentElement.setAttribute("data-sidebar-size", "lg");
			if (document.querySelector(".hamburger-icon")) document.querySelector(".hamburger-icon").classList.add("open");
		}
	}

	/**
	 * Позиционирование выпадающих меню
	 */
	function menuItem(e) {
		if (e.target && (e.target.matches("a.nav-link span") || e.target.matches("a.nav-link"))) {
			var nextEl = e.target.matches("a.nav-link span") ? e.target.parentElement.nextElementSibling : e.target.nextElementSibling;
			if (!elementInViewport(nextEl)) {
				nextEl.classList.add("dropdown-custom-right");
				var parentCollapse = e.target.closest(".collapse.menu-dropdown");
				if (parentCollapse) parentCollapse.classList.add("dropdown-custom-right");
			} else if (window.innerWidth >= 1848) {
				var elements = document.getElementsByClassName("dropdown-custom-right");
				while (elements.length > 0) elements[0].classList.remove("dropdown-custom-right");
			}
		}
	}

	/**
	 * Переключение гамбургер-меню
	 */
	function toggleHamburgerMenu() {
		var windowSize = document.documentElement.clientWidth;
		if (windowSize > 767) document.querySelector(".hamburger-icon")?.classList.toggle("open");
		if (document.documentElement.getAttribute("data-layout") === "semibox" && hasNavbarMenu) {
			if (windowSize > 767) {
				if (document.documentElement.getAttribute("data-sidebar-visibility") == "show") {
					var currentSize = document.documentElement.getAttribute("data-sidebar-size");
					if (currentSize === "lg") {
						document.documentElement.setAttribute("data-sidebar-size", "sm-hover");
						sessionStorage.setItem("data-sidebar-size", "sm-hover");
					} else {
						document.documentElement.setAttribute("data-sidebar-size", "lg");
						sessionStorage.setItem("data-sidebar-size", "lg");
					}
				}
			} else if (windowSize <= 767) {
				if (document.body.classList.contains("vertical-sidebar-enable")) {
					document.body.classList.remove("vertical-sidebar-enable");
				} else {
					document.body.classList.add("vertical-sidebar-enable");
					document.body.classList.add("twocolumn-panel");
				}
				document.documentElement.setAttribute("data-sidebar-size", "lg");
			}
		}
	}

	/**
	 * Загрузка контента окна
	 */
	function windowLoadContent() {
		document.addEventListener("DOMContentLoaded", function () {
			if (typeof feather !== "undefined") feather.replace();
		});
		window.addEventListener("resize", windowResizeHover);
		windowResizeHover();
		if (typeof Waves !== "undefined") Waves.init();
		document.addEventListener("scroll", windowScroll);
		window.addEventListener("load", function () {
			initActiveMenu();
			isLoadBodyElement();
			initModeSetting();
		});
		if (document.getElementById("topnav-hamburger-icon")) {
			var hamburgerIcon = document.querySelector(".hamburger-icon");
			if (document.documentElement.getAttribute("data-sidebar-size") === "sm-hover") {
				hamburgerIcon?.classList.add("open");
			} else {
				hamburgerIcon?.classList.remove("open");
			}
			document.getElementById("topnav-hamburger-icon").addEventListener("click", toggleHamburgerMenu);
		}
	}

	/**
	 * Тень верхней панели при прокрутке
	 */
	function windowScroll() {
		var pageTopbar = document.getElementById("page-topbar");
		if (pageTopbar) {
			(document.body.scrollTop >= 50 || document.documentElement.scrollTop >= 50) ?
				pageTopbar.classList.add("topbar-shadow") :
				pageTopbar.classList.remove("topbar-shadow");
		}
	}

	/**
	 * Активация текущего пункта меню
	 */
	function initActiveMenu() {
		var currentPath = location.pathname == "/" ? "index.html" : location.pathname.substring(1);
		currentPath = currentPath.substring(currentPath.lastIndexOf("/") + 1);
		if (currentPath) {
			var a = document.getElementById("navbar-nav")?.querySelector('[href="' + currentPath + '"]');
			if (a) {
				a.classList.add("active");
				var parentCollapseDiv = a.closest(".collapse.menu-dropdown");
				if (parentCollapseDiv) {
					parentCollapseDiv.classList.add("show");
					parentCollapseDiv.parentElement.children[0].classList.add("active");
					parentCollapseDiv.parentElement.children[0].setAttribute("aria-expanded", "true");
				}
			}
		}
	}

	/**
	 * Инициализация Bootstrap-компонентов
	 */
	function initComponents() {
		var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
		tooltipTriggerList.map(function (tooltipTriggerEl) {
			return new bootstrap.Tooltip(tooltipTriggerEl);
		});
		var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
		popoverTriggerList.map(function (popoverTriggerEl) {
			return new bootstrap.Popover(popoverTriggerEl);
		});
	}

	/**
	 * Анимация счетчиков
	 */
	function counter() {
		var counter = document.querySelectorAll(".counter-value");
		var speed = 250;
		counter && Array.from(counter).forEach(function (counter_value) {
			function updateCount() {
				var target = +counter_value.getAttribute("data-target");
				var count = +counter_value.innerText;
				var inc = target / speed;
				if (inc < 1) inc = 1;
				if (count < target) {
					counter_value.innerText = (count + inc).toFixed(0);
					setTimeout(updateCount, 1);
				} else {
					counter_value.innerText = numberWithCommas(target);
				}
			}
			updateCount();
		});
		function numberWithCommas(x) {
			return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
		}
	}

	/**
	 * Инициализация переключения тем
	 */
	function initModeSetting() {
		var html = document.documentElement;
		var lightDarkBtn = document.querySelectorAll(".light-dark-mode");
		if (lightDarkBtn && lightDarkBtn.length) {
			lightDarkBtn[0].addEventListener("click", function (event) {
				if (html.getAttribute("data-bs-theme") === "dark") {
					html.setAttribute("data-bs-theme", "light");
					sessionStorage.setItem("data-bs-theme", "light");
				} else {
					html.setAttribute("data-bs-theme", "dark");
					sessionStorage.setItem("data-bs-theme", "dark");
				}
				window.dispatchEvent(new Event("resize"));
			});
		}
	}

	/**
	 * Установка атрибутов макета
	 */
	function setDefaultAttribute() {
		var html = document.documentElement;
		html.setAttribute("data-theme", "material");
		html.setAttribute("data-theme-colors", "default");
		html.setAttribute("data-layout", "semibox");
		html.setAttribute("data-preloader", "disable");
		html.setAttribute("data-sidebar-visibility", hasNavbarMenu ? "show" : "hidden");
		html.setAttribute("data-layout-width", "fluid");
		html.setAttribute("data-layout-position", "fixed");
		html.setAttribute("data-topbar", "light");
		html.setAttribute("data-layout-style", "default");
		html.setAttribute("data-sidebar", "dark");
		html.setAttribute("data-sidebar-size", sessionStorage.getItem("data-sidebar-size") || "lg");
		html.setAttribute("data-bs-theme", sessionStorage.getItem("data-bs-theme") || "light");

		initLeftMenuCollapse();
	}

	/**
	 * Инициализация
	 */
	function init() {
		setDefaultAttribute();
		isCollapseMenu();
		pluginData();
		windowLoadContent();
		counter();
		initComponents();
	}

	init();
})();

// Кнопка "Наверх"
var mybutton = document.getElementById("back-to-top");
if (mybutton) {
	window.onscroll = function () {
		if (document.body.scrollTop > 100 || document.documentElement.scrollTop > 100) {
			mybutton.style.display = "block";
		} else {
			mybutton.style.display = "none";
		}
	};
	mybutton.addEventListener("click", function () {
		document.body.scrollTop = 0;
		document.documentElement.scrollTop = 0;
	});
}