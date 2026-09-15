(function () {
	"use strict";

	function panelsOf(wrap) {
		return Array.prototype.slice.call(wrap.querySelectorAll("[role=\"tabpanel\"]"));
	}

	function tabsOf(wrap) {
		return Array.prototype.slice.call(wrap.querySelectorAll("[role=\"tab\"]"));
	}

	function isDesktopCarousel() {
		return window.matchMedia("(min-width: 801px)").matches;
	}

	function prefersReducedMotion() {
		return window.matchMedia("(prefers-reduced-motion: reduce)").matches;
	}

	function slideTo(wrap, panel) {
		var track = wrap.querySelector(".low-cnt-track");
		if (!track || !panel) {
			return;
		}

		if (!isDesktopCarousel()) {
			wrap.style.setProperty("--low-cnt-shift", "0px");
			return;
		}

		wrap.style.setProperty("--low-cnt-shift", (-1 * panel.offsetLeft) + "px");
	}

	function scrollPanelIntoView(panel) {
		if (!panel || !panel.scrollIntoView) {
			return;
		}

		panel.scrollIntoView({
			behavior: prefersReducedMotion() ? "auto" : "smooth",
			block: "start",
			inline: "nearest"
		});
	}

	function activate(wrap, tab) {
		var wasActive = tab.classList.contains("is-active");
		var tabs = tabsOf(wrap);
		var panels = panelsOf(wrap);
		var panelId = tab.getAttribute("aria-controls");
		var activePanel = null;

		tabs.forEach(function (item) {
			var on = item === tab;
			item.classList.toggle("is-active", on);
			item.setAttribute("aria-selected", on ? "true" : "false");
			item.tabIndex = on ? 0 : -1;
		});

		panels.forEach(function (panel) {
			var on = panel.id === panelId;
			panel.classList.toggle("is-active", on);
			panel.setAttribute("aria-hidden", on ? "false" : "true");
			if (on) {
				activePanel = panel;
			}
		});

		if (!activePanel) {
			return;
		}

		if (isDesktopCarousel()) {
			slideTo(wrap, activePanel);
			if (tab.scrollIntoView) {
				tab.scrollIntoView({
					inline: "nearest",
					block: "nearest",
					behavior: prefersReducedMotion() ? "auto" : "smooth"
				});
			}
			return;
		}

		wrap.style.setProperty("--low-cnt-shift", "0px");
		if (!wasActive) {
			scrollPanelIntoView(activePanel);
		}
	}

	function bind(wrap) {
		var tabs = tabsOf(wrap);
		if (!tabs.length) {
			return;
		}

		wrap.addEventListener("click", function (event) {
			var tab = event.target.closest("[role=\"tab\"]");
			if (tab && wrap.contains(tab)) {
				activate(wrap, tab);
				return;
			}

			if (!isDesktopCarousel()) {
				return;
			}

			if (event.target.closest("a, button, input, textarea, select")) {
				return;
			}

			var panel = event.target.closest("[role=\"tabpanel\"]");
			if (!panel || !wrap.contains(panel)) {
				return;
			}

			var match = wrap.querySelector("[aria-controls=\"" + panel.id + "\"]");
			if (match) {
				activate(wrap, match);
			}
		});

		wrap.addEventListener("keydown", function (event) {
			var tab = event.target.closest("[role=\"tab\"]");
			if (!tab || !wrap.contains(tab)) {
				return;
			}

			var index = tabs.indexOf(tab);
			if (index < 0) {
				return;
			}

			var next = index;
			if (event.key === "ArrowRight" || event.key === "ArrowDown") {
				next = (index + 1) % tabs.length;
			} else if (event.key === "ArrowLeft" || event.key === "ArrowUp") {
				next = (index - 1 + tabs.length) % tabs.length;
			} else if (event.key === "Home") {
				next = 0;
			} else if (event.key === "End") {
				next = tabs.length - 1;
			} else {
				return;
			}

			event.preventDefault();
			tabs[next].focus();
			activate(wrap, tabs[next]);
		});

		var onResize = function () {
			var active = wrap.querySelector(".low-cnt-panel.is-active") || panelsOf(wrap)[0];
			slideTo(wrap, active);
		};

		window.addEventListener("resize", onResize);

		if (typeof ResizeObserver !== "undefined") {
			var viewport = wrap.querySelector(".low-cnt-viewport");
			if (viewport) {
				new ResizeObserver(onResize).observe(viewport);
			}
		}

		onResize();
	}

	document.querySelectorAll(".low-cnt-wrap").forEach(bind);
})();
