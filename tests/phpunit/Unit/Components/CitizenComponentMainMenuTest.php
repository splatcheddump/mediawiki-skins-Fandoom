<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen\Tests\Unit\Components;

use MediaWiki\Skins\Citizen\Components\CitizenComponent;
use MediaWiki\Skins\Citizen\Components\CitizenComponentMainMenu;
use MediaWikiUnitTestCase;

/**
 * @group Citizen
 * @group Components
 * @coversDefaultClass \MediaWiki\Skins\Citizen\Components\CitizenComponentMainMenu
 */
class CitizenComponentMainMenuTest extends MediaWikiUnitTestCase {

	/**
	 * This test checks if the CitizenComponentMainMenu class can be instantiated
	 * @covers ::__construct
	 */
	public function testConstruct() {
		// Mock the sidebar data
		$sidebarData = [];

		// Create a new CitizenComponentMainMenu object
		$mainMenu = new CitizenComponentMainMenu( $sidebarData );

		// Assert that the object is an instance of CitizenComponent
		$this->assertInstanceOf( CitizenComponent::class, $mainMenu );
	}

	/**
	 * @covers ::__construct
	 * @covers ::getTemplateData
	 * @dataProvider provideMainMenuData
	 */
	public function testGetTemplateData( array $sidebarData ) {
		// Create a new CitizenComponentMainMenu object
		$mainMenu = new CitizenComponentMainMenu( $sidebarData );

		// Call the getTemplateData method
		$templateData = $mainMenu->getTemplateData();

		// Assert the structure and types of expected keys
		$this->assertIsArray( $templateData['data-portlets-first'] );
		$this->assertIsArray( $templateData['array-portlets-rest'] );

		// Assert the structure and types of expected keys
		$this->assertArrayHasKey( 'data-portlets-first', $templateData );
		$this->assertArrayHasKey( 'array-portlets-rest', $templateData );
	}

	public static function provideMainMenuData(): iterable {
		yield 'empty sidebar data' => [
			'sidebarData' => [
				'data-portlets-first' => [],
				'array-portlets-rest' => [],
			]
		];
	}

	/**
	 * @covers ::buildNavigationTree
	 * @covers ::buildPortletTree
	 * @covers ::buildTreeFromFlatList
	 */
	public function testTreeBuildingFromFlatList() {
		$sidebarData = [
			'data-portlets-first' => [
				'array-list-items' => [
					[
						'text' => 'Items',
						'item-level' => 0,
					],
					[
						'text' => 'Weapons',
						'item-level' => 1,
					],
					[
						'text' => 'Swords',
						'item-level' => 2,
					],
					[
						'text' => 'Axes',
						'item-level' => 2,
					],
					[
						'text' => 'Armor',
						'item-level' => 1,
					],
				]
			],
			'array-portlets-rest' => []
		];

		$mainMenu = new CitizenComponentMainMenu( $sidebarData, 'citizen-main-menu', true );
		$templateData = $mainMenu->getTemplateData();

		$firstPortlet = $templateData['data-portlets-first'];
		$this->assertCount( 1, $firstPortlet['array-list-items'] );
		
		$items = $firstPortlet['array-list-items'][0];
		$this->assertSame( 'Items', $items['text'] );
		$this->assertTrue( $items['has-children'] );
		$this->assertCount( 2, $items['array-children'] );

		$weapons = $items['array-children'][0];
		$this->assertSame( 'Weapons', $weapons['text'] );
		$this->assertTrue( $weapons['has-children'] );
		$this->assertCount( 2, $weapons['array-children'] );

		$swords = $weapons['array-children'][0];
		$this->assertSame( 'Swords', $swords['text'] );

		$axes = $weapons['array-children'][1];
		$this->assertSame( 'Axes', $axes['text'] );

		$armor = $items['array-children'][1];
		$this->assertSame( 'Armor', $armor['text'] );
	}
}
