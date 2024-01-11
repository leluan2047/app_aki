<?php
/* ActiveFusions 2015/11/09 16:09 */

namespace Plugin\MailTemplateEdit;

use Eccube\Event\RenderEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\CssSelector\CssSelector;
use Symfony\Component\DomCrawler\Crawler;

class MailTemplateEdit{

	private $app;

	public function __construct($app){
		$this->app = $app;
	}

	public function onRenderMailTemplate(){
















	}
}
