<?php

namespace TRC\UserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class TRCUserBundle extends Bundle
{
	public function getParent(){

		return 'FOSUserBundle';
	}
}

