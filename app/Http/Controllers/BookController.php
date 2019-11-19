<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Book as Book ;
use App\Mail\BookAvailable ;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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
			try{
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
			}catch(\Exception $e){
				return redirect()->back()->with('error', 'Looks like a field is missing, author:title:url');
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
		$context = stream_context_create(array(
	        "http" => array(
	            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
		        )
		    )
		);
		$books = Book::where('available', false)->get();

		foreach($books as $book)
		{
			try{
				$page = file_get_contents($book->url, false, $context);
			}catch(\Exception $e){
				return redirect()->back()->with('error', "hmm couldn't find this book $book->title");
			}

			if(Str::contains($book->url, 'mackbooks')){
				if(Str::contains($page, 'Available to pre-order')){
					$book->available = false;
					$book->save();
				}else{
					$this->notify($book);
				}
			}else{
				preg_match('/<div class=\"headline headline\-left\">(.*?)<\/div>/s', $page, $matches);
			
				if(Str::contains($matches[0], 'Not yet published')){
					$book->available = false;
					$book->save();
				}elseif(Str::contains($matches[0], 'Free shipping')){
					$this->notify($book);
				}
			}

			$page = null;
		}

		return redirect()->route('index');
	}

	private function notify($book){
		$book->available = true;
		$book->save();

        Mail::to('jerome.arfouche@gmail.com')->send(new BookAvailable($book));
	}
}
