<?php
namespace Choumei;
use Illuminate\Support\ServiceProvider;
class ChoumeiServiceProvider extends ServiceProvider{
	public function register(){
		$this->app['Choumei'] = $this->app->share(
			function($app){
				return new \Choumei\Choumei();
			}
		);

		$this->app->booting(
			function(){
				$aliases = \Config::get('app.aliases');
				if(empty($aliases['ChoumeiFacade'])){
					$loader = \Illuminate\Foundation\AliasLoader::getInstance();
					$loader->alias('Choumei','Choumei\ChoumeiFacade');
				}
			}
		);
	}
}
?>