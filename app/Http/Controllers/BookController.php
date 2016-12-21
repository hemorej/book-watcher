<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Book as Book ;
use App\Mail\BookAvailable ;
use Illuminate\Support\Facades\Mail;

class BookController extends Controller
{

	public function index(Request $request)
	{
		$viewData['books'] = Book::orderBy('available')->orderBy('author')->get() ;
    	return view('index')->with($viewData) ;
	}

	public function create(Request $request)
	{
		$input = preg_split('/\r\n/', $request->get('urls'));

		foreach ($input as $line) {
			list($author, $title, $url) = explode(':', $line, 3);
			$author = trim($author);
			$title = trim($title);
			$url = trim($url);

			if(filter_var($url, FILTER_VALIDATE_URL) && empty(Book::where('url', $url)->first())){
				$book = new Book();
				$book->url = $url;
				$book->author = $author;
				$book->title = $title;
				$book->save();
			}
		}

		return redirect()->route('index');
	}

	public function delete(Request $request)
	{
		Book::where('id', $request->input('tid'))->delete() ;
		return redirect()->route('index');
	}

	public function check(Request $request)
	{
		$books = Book::where('available', false)->get();

		foreach($books as $book)
		{
			$page = file_get_contents($book->url);
			preg_match('/<div class=\"headline headline\-left\">(.*?)<\/div>/s', $page, $matches);

			if(str_contains($matches[0], 'Not yet published')){
				$book->available = false;
				$book->save();
			}elseif(str_contains($matches[0], 'Free shipping')){
				$book->available = true;
				$book->save();

                Mail::to('jerome.arfouche@gmail.com')->send(new BookAvailable($book));
			}
			$page = null;
		}

		return redirect()->route('index');
	}
}
