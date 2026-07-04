// @vitest-environment jsdom

const { positionSubmenuList } = require( '../../../resources/skins.citizen.scripts/navbarSubmenu.js' );

describe( 'positionSubmenuList', () => {
	it( 'centers a fixed submenu beneath its trigger', () => {
		const list = document.createElement( 'ul' );
		const trigger = document.createElement( 'button' );
		const windowMock = { innerWidth: 1000, innerHeight: 800 };

		trigger.getBoundingClientRect = () => ( {
			left: 200,
			top: 40,
			bottom: 80,
			width: 100,
			height: 40
		} );

		Object.defineProperty( list, 'offsetWidth', {
			configurable: true,
			get: () => 180
		} );

		positionSubmenuList( trigger, list, windowMock );

		expect( list.style.position ).toBe( 'fixed' );
		expect( list.style.top ).toBe( '88px' );
		expect( list.style.left ).toBe( '160px' );
	} );
} );
