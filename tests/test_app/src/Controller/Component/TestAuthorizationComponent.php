<?php declare(strict_types=1);

namespace TestApp\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;

class TestAuthorizationComponent extends Component {

	public function skipAuthorization(): void {
		Configure::write('Test.authorizationSkipped', true);
	}

}
