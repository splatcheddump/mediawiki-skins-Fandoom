<?php

declare( strict_types=1 );

namespace MediaWiki\Skins\Citizen\Components;

/**
 * CitizenComponentMainMenu component
 */
class CitizenComponentMainMenu implements CitizenComponent {

	public function __construct(
		private readonly array $sidebarData,
		private readonly string $id = 'citizen-main-menu'
	) {
	}

	public function getTemplateData(): array {
		return [
			'id' => $this->id,
			'data-portlets-first' => (
				new CitizenComponentMenu( $this->sidebarData['data-portlets-first'] )
			)->getTemplateData(),
			'array-portlets-rest' => array_map(
				static fn ( array $data ): array => ( new CitizenComponentMenu( $data ) )->getTemplateData(),
				$this->sidebarData[ 'array-portlets-rest' ]
			)
		];
	}
}
