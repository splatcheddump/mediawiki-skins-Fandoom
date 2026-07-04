/**
 * Navbar submenu panel system for mobile drawer.
 *
 * On mobile (inside .citizen-drawer__menu), clicking a parent nav item
 * slides in a nested panel rather than using the hover-based absolute
 * dropdown that is used on desktop.
 */

/**
 * Position a submenu list fixed beneath its trigger while keeping it centered.
 *
 * @param {HTMLElement} trigger
 * @param {HTMLElement} list
 * @param {Window} windowObject
 * @return {void}
 */
function positionSubmenuList(trigger, list, windowObject) {
	const triggerRect = trigger.getBoundingClientRect();
	const listWidth = list.offsetWidth || 0;
	const left = Math.min(
		Math.max(triggerRect.left + (triggerRect.width / 2) - (listWidth / 2), 8),
		windowObject.innerWidth - listWidth - 8
	);

	list.style.position = 'fixed';
	list.style.top = `${triggerRect.bottom + 8}px`;
	list.style.left = `${left}px`;
}

/**
 * Build a panel element for a given submenu list.
 *
 * @param {string} title   Label shown at the top of the panel (the parent item's text)
 * @param {HTMLElement} ul The <ul class="utw-navbar-menu"> that holds the children
 * @return {HTMLElement}
 */
function createPanel(title, ul) {
	const panel = document.createElement('div');
	panel.className = 'utw-drawer-panel';

	// Back button row
	const header = document.createElement('div');
	header.className = 'utw-drawer-panel__header';

	const backBtn = document.createElement('button');
	backBtn.className = 'utw-drawer-panel__back';
	backBtn.type = 'button';
	backBtn.setAttribute('aria-label', mw.msg('citizen-back') || 'Back');

	const backIcon = document.createElement('span');
	backIcon.className = 'citizen-ui-icon mw-ui-icon-wikimedia-arrowPrevious';
	backBtn.appendChild(backIcon);

	const titleEl = document.createElement('span');
	titleEl.className = 'utw-drawer-panel__title';
	titleEl.textContent = title;

	header.appendChild(backBtn);
	header.appendChild(titleEl);

	// Clone the child list into the panel
	const listClone = ul.cloneNode(true);
	listClone.classList.add('utw-drawer-panel__list');

	panel.appendChild(header);
	panel.appendChild(listClone);

	return panel;
}

/**
 * Wire up the panel stack inside a single drawer menu section.
 *
 * @param {HTMLElement} menuRoot  The .citizen-drawer__menu element
 */
function initDrawerMenu(menuRoot) {
	/** @type {HTMLElement[]} Stack of active panels (most-recent last) */
	const panelStack = [];

	// Container that wraps menu content + panels with overflow:hidden
	const container = document.createElement('div');
	container.className = 'utw-drawer-panel-container';

	// Wrap the existing menu content
	const originalContent = Array.from(menuRoot.childNodes);
	originalContent.forEach((node) => container.appendChild(node));
	menuRoot.appendChild(container);

	/**
	 * Push a new panel onto the stack.
	 *
	 * @param {HTMLElement} panel
	 */
	function pushPanel(panel) {
		panelStack.push(panel);
		container.appendChild(panel);
		// Trigger transition on next frame
		requestAnimationFrame(() => {
			panel.classList.add('utw-drawer-panel--active');
		});

		// Wire back button
		const backBtn = panel.querySelector('.utw-drawer-panel__back');
		if (backBtn) {
			backBtn.addEventListener('click', () => popPanel());
		}

		// Wire nested parent items inside this panel
		wireParentItems(panel);
	}

	/**
	 * Pop the topmost panel.
	 */
	function popPanel() {
		if (panelStack.length === 0) {
			return;
		}
		const panel = panelStack.pop();
		panel.classList.remove('utw-drawer-panel--active');
		panel.addEventListener('transitionend', () => {
			if (!panel.classList.contains('utw-drawer-panel--active')) {
				panel.remove();
			}
		}, { once: true });
	}

	/**
	 * Attach click handlers to all .utw-navbar-parent items inside a root.
	 *
	 * @param {HTMLElement} root
	 */
	function wireParentItems(root) {
		root.querySelectorAll('.utw-navbar-parent').forEach((li) => {
			// Only wire direct children of this root to avoid double-wiring
			if (li.closest('.utw-drawer-panel') !== root.closest('.utw-drawer-panel') &&
				root.closest('.utw-drawer-panel') !== null) {
				return;
			}

			const trigger = li.querySelector(':scope > .utw-navbar-subitem-link, :scope > .utw-navbar-subitem-text');
			const submenuUl = li.querySelector(':scope > .utw-navbar-subitem-collapsable > .utw-navbar-menu');

			if (!trigger || !submenuUl) {
				return;
			}

			// Mark as wired to prevent double-wiring
			if (li.dataset.drawerWired) {
				return;
			}
			li.dataset.drawerWired = '1';

			// Prevent navigating to the parent link (if it's an anchor)
			if (trigger.tagName === 'A') {
				trigger.addEventListener('click', (e) => {
					e.preventDefault();
					const labelEl = trigger.querySelector('span') || trigger;
					pushPanel(createPanel(labelEl.textContent.trim(), submenuUl));
				});
			} else {
				trigger.addEventListener('click', () => {
					pushPanel(createPanel(trigger.textContent.trim(), submenuUl));
				});
			}

			// Add a chevron button next to the link for accessibility
			let chevronBtn = li.querySelector('.utw-drawer-chevron');
			if (!chevronBtn) {
				chevronBtn = document.createElement('button');
				chevronBtn.type = 'button';
				chevronBtn.className = 'utw-drawer-chevron cdx-button cdx-button--fake-button cdx-button--weight-quiet';
				chevronBtn.setAttribute('aria-label', 'Open submenu');
				const icon = document.createElement('span');
				icon.className = 'citizen-ui-icon mw-ui-icon-wikimedia-arrowNext';
				chevronBtn.appendChild(icon);
				li.appendChild(chevronBtn);

				chevronBtn.addEventListener('click', (e) => {
					e.stopPropagation();
					const labelEl = trigger.querySelector('span') || trigger;
					pushPanel(createPanel(labelEl.textContent.trim(), submenuUl));
				});
			}
		});
	}

	// Wire the initial menu items
	wireParentItems(container);
}

/**
 * Initialise submenu panels for all drawer menus on the page.
 *
 * @param {Object} params
 * @param {Document} params.document
 */
function initDesktopSubmenus({ document, window }) {
	if (!window.matchMedia('(min-width: 1200px)').matches) {
		return;
	}

	document.querySelectorAll('.citizen-header .citizen-main-menu .citizen-menu').forEach((menu) => {
		const trigger = menu.querySelector(':scope > .citizen-menu__heading');
		const list = menu.querySelector(':scope > .citizen-menu__content-list');

		if (!trigger || !list) {
			return;
		}

		const position = () => positionSubmenuList(trigger, list, window);
		const schedulePosition = () => window.requestAnimationFrame(position);

		['mouseenter', 'focusin', 'touchstart'].forEach((eventName) => {
			trigger.addEventListener(eventName, schedulePosition);
			menu.addEventListener(eventName, schedulePosition);
		});

		window.addEventListener('resize', schedulePosition);
		window.addEventListener('scroll', schedulePosition, true);
		position();
	});
}

/**
 * Initialise submenu positioning for desktop dropdowns and drawer panels for mobile.
 *
 * @param {Object} params
 * @param {Document} params.document
 * @param {Window} params.window
 */
function init({ document, window }) {
	initDesktopSubmenus({ document, window });

	// Only activate inside the drawer (mobile); desktop uses CSS hover
	const isMobile = !window.matchMedia('(min-width: 1200px)').matches;
	if (!isMobile) {
		return;
	}

	document.querySelectorAll('.citizen-drawer__menu').forEach((menu) => {
		initDrawerMenu(menu);
	});
}

module.exports = { init, positionSubmenuList };
