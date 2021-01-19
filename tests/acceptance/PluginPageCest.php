<?php

class PluginPageCest {

	// tests
	public function displaySettingsLinkAdminPluginList( AcceptanceTester $I ) {
		$I->loginAsAdmin();
		$I->amOnPage( '/wp-admin/plugins.php' );
		$I->see( ' Settings ' );
	}
}
