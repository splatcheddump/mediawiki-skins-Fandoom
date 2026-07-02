<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen\Components;

/**
 * CitizenComponentMainMenu component
 */
class CitizenComponentMainMenu implements CitizenComponent {

	public function __construct(
		private readonly array $sidebarData,
		private readonly string $id = 'citizen-main-menu',
		private readonly bool $enableTree = false
	) {
	}

	public function getTemplateData(): array {
		$sidebarData = $this->enableTree ? $this->buildNavigationTree( $this->sidebarData ) : $this->sidebarData;

		return [
			'id' => $this->id,
			'data-portlets-first' => (
				new CitizenComponentMenu( $sidebarData['data-portlets-first'] )
			)->getTemplateData(),
			'array-portlets-rest' => array_map(
				static fn ( array $data ): array => ( new CitizenComponentMenu( $data ) )->getTemplateData(),
				$sidebarData[ 'array-portlets-rest' ]
			)
		];
	}

	private function buildNavigationTree( array $sidebarData ): array {
		$sidebarData['data-portlets-first'] = $this->buildPortletTree( $sidebarData['data-portlets-first'] );
		$sidebarData['array-portlets-rest'] = array_map(
			fn ( array $portlet ): array => $this->buildPortletTree( $portlet ),
			$sidebarData['array-portlets-rest']
		);

		return $sidebarData;
	}

	private function buildPortletTree( array $portlet ): array {
		$listItems = $portlet['array-list-items'] ?? null;
		if ( !$listItems ) {
			return $portlet;
		}

		$tree = [];
		foreach ( $listItems as $item ) {
			$this->insertTreeItem( $tree, $item, $this->getTreePath( $item ) );
		}

		$portlet['array-list-items'] = array_values( $tree );
		return $portlet;
	}

	private function insertTreeItem( array &$tree, array $item, array $path ): void {
		if ( count( $path ) <= 1 ) {
			$tree[] = $item + [ 'array-children' => [] ];
			return;
		}

		$label = array_shift( $path );
		$index = $this->findTreeItemIndex( $tree, $label );

		if ( $index === null ) {
			$parent = $this->createTreeParentItem( $item, $label );
			$tree[] = $parent;
			$index = array_key_last( $tree );
		} elseif ( !isset( $tree[$index]['array-children'] ) ) {
			$tree[$index]['array-children'] = [];
		}

		$this->insertTreeItem( $tree[$index]['array-children'], $this->withTreeLabel( $item, $path[0] ), $path );
		$tree[$index]['has-children'] = true;
	}

	private function findTreeItemIndex( array $tree, string $label ): ?int {
		foreach ( $tree as $index => $item ) {
			if ( $this->getItemText( $item ) === $label ) {
				return $index;
			}
		}

		return null;
	}

	private function createTreeParentItem( array $item, string $label ): array {
		$item = $this->withTreeLabel( $item, $label );
		$item['array-children'] = [];
		$item['has-children'] = true;
		$item['item-class'] = trim( ( $item['item-class'] ?? '' ) . ' citizen-menu__item--has-children' );

		return $item;
	}

	private function withTreeLabel( array $item, string $label ): array {
		if ( isset( $item['array-links'][0] ) ) {
			$item['array-links'][0]['text'] = $label;
		}

		$item['text'] = $label;
		return $item;
	}

	private function getTreePath( array $item ): array {
		$text = $this->getItemText( $item );
		$parts = preg_split( '/\s*(?:\/|>)\s*/', $text ) ?: [ $text ];

		return array_values( array_filter( $parts, static fn ( string $part ): bool => $part !== '' ) );
	}

	private function getItemText( array $item ): string {
		return (string)( $item['array-links'][0]['text'] ?? $item['text'] ?? '' );
	}
}
