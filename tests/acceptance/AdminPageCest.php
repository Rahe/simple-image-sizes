<?php 

class AdminPageCest
{

    // tests
    public function displayMediaAdminPage(AcceptanceTester $I) {
		$I->loginAsAdmin();
		$I->amOnPage('/wp-admin/options-media.php');
		$I->see('Add a new size');
		$I->see('Get php for theme');
		$I->see('Thumbnail regeneration');

		$I->seeInField('#ajax_thumbnail_rebuild', 'Regenerate Thumbnails');
		$I->seeInField('#add_size', 'Add a new size of thumbnail');
		$I->seeInField('#get_php', 'Get the PHP for the theme');

    }

    // tests
    public function AddSize(AcceptanceTester $I) {
		$I->loginAsAdmin();
		$I->amOnPage('/wp-admin/options-media.php');

		$I->click('#add_size');
		$I->fillField('#new_size_0', 'size-test');
		$I->click('#validate_0');
    }
}
