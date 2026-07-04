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

		// First, try to detect native nesting (array-children already set)
		$hasNativeNesting = false;
		foreach ( $listItems as $item ) {
			if ( isset( $item['array-children'] ) && is_array( $item['array-children'] ) && !empty( $item['array-children'] ) ) {
				$hasNativeNesting = true;
				break;
			}
		}

		if ( $hasNativeNesting ) {
			// Use native nesting from MediaWiki
			$portlet['array-list-items'] = array_map(
				fn ( array $item ): array => $this->markChildrenRecursively( $item ),
				$listItems
			);
			return $portlet;
		}

		// Try to detect item-level based nesting (flat list from * and ** syntax)
		$hasItemLevel = false;
		foreach ( $listItems as $item ) {
			if ( isset( $item['item-level'] ) ) {
				$hasItemLevel = true;
				break;
			}
		}

		if ( $hasItemLevel ) {
			// Build tree from flat list with item-level indicators
			$portlet['array-list-items'] = $this->buildTreeFromFlatList( $listItems );
			return $portlet;
		}

		// Otherwise, use items as-is (no nesting)
		return $portlet;
	}

	private function buildTreeFromFlatList( array $listItems ): array {
		$tree = [];
		$stack = []; // Stack of [level => item_reference]

		foreach ( $listItems as &$item ) {
			$level = (int)( $item['item-level'] ?? 0 );

			// Initialize children array if missing
			if ( !isset( $item['array-children'] ) ) {
				$item['array-children'] = [];
			}

			// Pop stack until we find a parent at the correct level
			while ( !empty( $stack ) && $stack[ count( $stack ) - 1 ]['level'] >= $level ) {
				array_pop( $stack );
			}

			// If we have a parent in the stack, add this as its child
			if ( !empty( $stack ) ) {
				$parent = &$stack[ count( $stack ) - 1 ]['item'];
				$parent['array-children'][] = &$item;
				$parent['has-children'] = true;
				$parent['item-class'] = trim( ( $parent['item-class'] ?? '' ) . ' citizen-menu__item--has-children' );
			} else {
				// Top-level item
				$tree[] = &$item;
			}

			// Add to stack for potential children
			$stack[] = [ 'level' => $level, 'item' => &$item ];
		}

		// Clean up references
		unset( $item );

		return $tree;
	}

	private function markChildrenRecursively( array $item ): array {
		if ( isset( $item['array-children'] ) && is_array( $item['array-children'] ) && !empty( $item['array-children'] ) ) {
			$item['has-children'] = true;
			$item['item-class'] = trim( ( $item['item-class'] ?? '' ) . ' citizen-menu__item--has-children' );
			$item['array-children'] = array_map(
				fn ( array $child ): array => $this->markChildrenRecursively( $child ),
				$item['array-children']
			);
		}
		return $item;
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
