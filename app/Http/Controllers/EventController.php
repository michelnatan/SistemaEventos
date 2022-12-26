<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Event;
use App\Models\User;

class EventController extends Controller
{
    
    public function index(){

        $search = request('search');

        if($search) {
            $events = Event::where([
                ['title','like','%'.$search.'%']
            ])->get();
        }else{
            $events = Event::all();
        }

        return view('welcome',['events' => $events, 'search' => $search]);
    }

    public function create(){
        return view('events.create');
    }

    public function products(){
        $busca = request('search');
        return view('products', ['busca' => $busca]);
    }

    public function products_test($id = null){
        return view('product', ['id' => $id ]);
    }

    public function store(Request $request){

        $event = new Event;

        $event->title = $request->title;
        $event->date = $request->date;
        $event->city = $request->city;
        $event->private = $request->private;
        $event->description = $request->description;
        $event->items = $request->items;

        //Image Upload
        if($request->hasFile('image') && $request->file('image')->isvalid()){

            $requestImage = $request->image;
            $extension = $requestImage->extension();
            //Criando um nome de arquivo unico em MD5 e com o tempo atual
            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension; 

            //Salvando imagem no diretorio
            $request->image->move(public_path('img/events'), $imageName);
            //Salvando no evento e no banco
            $event->image = $imageName;
        }

        $user = auth()->user();
        $event->user_id = $user->id;

        $event->save();

        return redirect('/')->with('msg','Evento criado com sucesso!');
        
    }

    public function show($id){

        $event = Event::findOrFail($id);//Padrão Laravel Eloquent

        $user = auth()->user();
        $hasUserJoined = false;

        if($user){

            $userEvents = $user->eventsAsParticipant->toArray();
            foreach($userEvents as $userEvent){
                if($userEvent['id']  == $id){
                    $hasUserJoined = true;
                }
            }

        }

        $eventOwner = User::where('id', $event->user_id)->first()->toArray();

        return view('events.show', ['event' => $event, 'eventOwner' => $eventOwner , 'hasUserJoined' => $hasUserJoined]);
    }

    public function dashboard(){

        $user = auth()->user();

        $events = $user->events;

        $eventsAsParticipant = $user->eventsAsParticipant;

        return view('events.dashboard', ['events' => $events, 'eventsasparticipant' => $eventsAsParticipant]);
    }

    public function destroy($id){

        Event::findOrFail($id)->delete();

        return redirect('/dashboard')->with('msg','Evento excluído com sucesso!');

    }

    public function edit($id){

        $user = auth()->user();

        $event =  Event::findOrFail($id);

        if($user->id != $event->user_id){
            return redirect('/dashboard');
        }

        return view('events.edit', ['event' => $event]);
    }

    public function update( Request $request){

        $data = $request->all();

        //Image Upload
        if($request->hasFile('image') && $request->file('image')->isvalid()){

            $requestImage = $request->image;
            $extension = $requestImage->extension();
            //Criando um nome de arquivo unico em MD5 e com o tempo atual
            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension; 

            //Salvando imagem no diretorio
            $request->image->move(public_path('img/events'), $imageName);
            //Salvando no evento e no banco
            $data['image'] = $imageName;
        }

        Event::findOrFail($request->id)->update($data);

        return redirect('/dashboard')->with('msg','Evento editado com sucesso!');

    }

    public function joinEvent($id){
    
        $user = auth()->user();

        //Faz ligação do evento com o user
        $user->eventsAsParticipant()->attach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg','Sua presença está confirmada no evento ' . $event->title);
    }

    public function leaveEvent($id){

        $user = auth()->user();

        //deleta a ligação o user no evento
        $user->eventsAsParticipant()->detach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg','Você saiu com sucesso do evento ' . $event->title);
    }

}

