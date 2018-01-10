<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class overviewTest extends DuskTestCase
{
    /**
     * A Dusk test example.
     *
     * @return void
     */
    public function Title()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('Statische Codeanalyse für unerreichbare Codeelemente');
        });
    }
    public function newProject() {
        
        
        $this->browse(function (Browser $browser) {
            $browser->type('#projectname', "Mein Projektname")
                    ->click('#create')
                ->assertQueryStringHas("area","file");
        });
    }
    
    public function editProject() {
        
        
        $this->browse(function (Browser $browser) {
            $browser->type('#upload', "classcalls")
                    ->click('#goto')
                ->assertQueryStringHas("area","file");
        });
    }
}
