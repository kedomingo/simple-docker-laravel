<?php

namespace App\Http\Controllers;

use App\Models\Migration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SampleController extends Controller
{
    public function giveMeMail()
    {
        // Get something from the db
        $migrations = Migration::all()->toArray();
        $content = implode(
            '<br/>',
            array_map(
                function ($row) {
                    return $row['migration'];
                },
                $migrations
            )
        );

        Mail::to('you@email.com')->send(new TestMail($content));

        return "You have mail!";
    }
}


class TestMail extends Mailable implements ShouldQueue
{

    /**
     * @var string
     */
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function build()
    {
        $html = '<h1>These are your migrations</h1>' . $this->content;

        return $this->from('admin@example.com')
            ->html($html);
    }
}
