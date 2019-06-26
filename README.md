# CRUD GENERATOR for Laravel
Needs a simple and fast CRUD system?
Crud Generator provides Model, Controller, Routes and Views based on given Migration.

## Installing via Composer

```bash
composer require matheuscarvalho/crudgenerator
```

## Add the provider to config/app.php providers array

```php
'providers' => [
...
/*
 * Package Service Providers...
 */
Matheuscarvalho\Crudgenerator\Src\CrudGeneratorServiceProvider::class,
...
],
```

## Publish the CSS and Config folder
```bash
php artisan config:cache
php artisan vendor:publish
```

## Usage

1. Create your migration and fill with desired fields.

2. Copy the name of generated migration.

3. Open a cmd on project root and type:
```bash
php artisan generate:crud migration_name --model-name model_name
```

## Full Example

1. Creating the migration.
```bash 
php artisan make:migration create_products_table
```

2. Filling up the migration.
```php
public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->double('price')->nullable();
            $table->integer('quantity')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->date('date')->nullable();
            $table->dateTime('date_time')->nullable();
            $table->time('time')->nullable();
            $table->unsignedInteger('fabrication_country_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
```

3. Generating the CRUD.
```bash
php artisan generate:crud 2019_06_19_024333_create_products_table.php --model-name Product
```

## Important notes

- Please create all your foreign keys following the pattern on example:
 
    `$table->unsignedInteger('fabrication_country_id')->nullable();`
    
The crud-generator uses the 'unsignedInteger' type to search foreign keys in migrations and uses the snake_case pattern to create Model, Controller and Views.

## Options

Option | params | Description
------------ | -------- | -------------
--model-name | string | The name used by crud-generator to create the files
--without-style | none | A boolean option which disable the default style. By default, the generated views comes with a simple css (that uses bootstrap classes) to style basically the pages.
--language | [ br, en ] | Specifies the language of files generated. Default = en.

## Configs

The package comes with a file which allows you to override some default configurations.
After you had published the css and the config folders, you can navigate to config/crudconfig.php and edit some configs, they are:

Config      | Default   | Description
------------|-----------|------------
language    | en        | Specifies the language of all texts inside the files generated. Accepts only 'br' for 'Português Brasileiro' or 'en' for 'English'

- After you edit any config inside crudconfig.php, please be sure of run the 
```bash 
php artisan config:cache
``` 
command to apply your changes.

## Output

After running the `php artisan generate:crud 2019_06_19_024333_create_products_table.php --model-name Product` 
command, you'll be able to find these files in your project:

<details>
<summary> App\Http\Controllers\ProductController.php </summary>

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\FabricationCountry;

class ProductController extends Controller
{
	public function index() { 
		$items = Product::all();
		return view('product.index', compact('items'));
	}

	public function create() { 
		$categorys = Category::all();
		$fabricationCountrys = FabricationCountry::all();
		return view('product.create', compact('categorys', 'fabricationCountrys'));
	}

	public function edit($id) { 
		$item = Product::find($id);
		$categorys = Category::all();
		$fabricationCountrys = FabricationCountry::all();
		return view('product.create', compact('categorys', 'fabricationCountrys', 'item'));
	}

	public function store() { 
		$data = request()->all();
		$insert = Product::create($data);
		if ($insert) {
			return redirect()->route('indexProduct')->with('message', 'Product inserted successfully');
		} else {
			return redirect()->back()->with('error', 'Insertion error');
		}
	}

	public function update($id) { 
		$data = request()->all();
		$item = Product::find($id);
		$update = $item->update($data);
		if ($update) {
			return redirect()->route('indexProduct');
		} else {
			return redirect()->back();
		}
	}

	public function destroy($id) { 
		$item = Product::find($id);
		$delete = $item->delete();
		if ($delete) {
			return redirect()->route('indexProduct')->with('message', 'Product deleted successfully');
		} else {
			return redirect()->back()->with('error', 'Deletion error');
		}
	}
}
```

</details>

<details>
<summary> App\Models\Product.php </summary>

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
	protected $table = 'products';

	protected $fillable = [
		'name',
		'price',
		'quantity',
		'category_id',
		'date',
		'date_time',
		'time',
		'fabrication_country_id',
	];

	public function Category(){
		return $this->belongsTo('App\Models\Category', 'category_id', 'id');
	}

	public function FabricationCountry(){
		return $this->belongsTo('App\Models\FabricationCountry', 'fabrication_country_id', 'id');
	}
}
```

</details>

<details>
<summary> routes\web.php </summary>

```php
[...]

Route::get('/product', 'ProductController@index')->name('indexProduct');
Route::get('/product/create', 'ProductController@create')->name('createProduct');
Route::get('/product/edit/{id}', 'ProductController@edit')->name('editProduct');
Route::post('/product/store', 'ProductController@store')->name('storeProduct');
Route::put('/product/update/{id}', 'ProductController@update')->name('updateProduct');
Route::delete('/product/delete/{id}', 'ProductController@destroy')->name('deleteProduct');
```

</details>


<details>
<summary> resources\views\product\create.blade.php </summary>

```php
<link href="{{asset('css/crudstyle.css')}}" rel='stylesheet'>

<title>Create Product</title>

<div>
	<div>
		<ul class='breadcrumb'>
			<li><a href="{{ route('indexProduct') }}">Product</a></li>
			<li class='active'>Create Product</li>
		</ul>
	</div>
</div>

<div>
	<form class='container' method='post' 
		@if(isset($item))
			action="{{ route('updateProduct', $item->id) }}">
			{!! method_field('PUT') !!}
		@else
			action="{{ route('storeProduct') }}">
		@endif
		{!! csrf_field() !!}
		<div>Name</div>
		<div>
			<input type='text' name='name' value="{{isset($item) ? $item->name : old('name')}}">
		</div>
		<div>Price</div>
		<div>
			<input type='number' step='0.01' name='price' value="{{isset($item) ? $item->price : old('price')}}">
		</div>
		<div>Quantity</div>
		<div>
			<input type='number' name='quantity' value="{{isset($item) ? $item->quantity : old('quantity')}}">
		</div>
		<div>Category</div>
		<div>
			<select name='category_id'>
				<option value='0'>Select the Category</option>
				@foreach($categorys as $fk)
					<option value="{{$fk->id}}" @if(isset($item) && $fk->id == $item->category_id) selected @endif>
						{{$fk->description}}
					</option>
				@endforeach
			</select>
		</div>
		<div>Date</div>
		<div>
			<input type='date' name='date' value="{{isset($item) ? $item->date : old('date')}}">
		</div>
		<div>Date time</div>
		<div>
			<input type='datetime-local' name='date_time' value="{{isset($item) ? str_replace(' ', 'T', $item->date_time) : old('date_time')}}">
		</div>
		<div>Time</div>
		<div>
			<input type='time' name='time' value="{{isset($item) ? $item->time : old('time')}}">
		</div>
		<div>Fabrication country</div>
		<div>
			<select name='fabrication_country_id'>
				<option value='0'>Select the Fabrication Country</option>
				@foreach($fabricationCountrys as $fk)
					<option value="{{$fk->id}}" @if(isset($item) && $fk->id == $item->fabrication_country_id) selected @endif>
						{{$fk->description}}
					</option>
				@endforeach
			</select>
		</div>

		<button class='btn btn-success'>Save</button>
	</form>
</div>
```

</details>

<details>
<summary> resources\views\product\index.blade.php </summary>

```php
<link href="{{asset('css/crudstyle.css')}}" rel='stylesheet'>

<title>Product</title>

<div class='container'>
	<a href="{{ route('createProduct') }}" class='btn btn-success'> New</a>

	@if (session('message'))
		<div class='alert alert-success'>
			{{ session('message') }}
		</div>
	@endif

	<table class='table'>
		<thead>
			<tr>
				<th>Name</th>
				<th>Price</th>
				<th>Quantity</th>
				<th>Category</th>
				<th>Date</th>
				<th>Date time</th>
				<th>Time</th>
				<th>Fabrication country</th>
				<th>Ações</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($items as $item)
			<tr>
				<td>{{$item->name}}</td>
				<td>{{$item->price}}</td>
				<td>{{$item->quantity}}</td>
				<td>{{$item->Category->description}}</td>
				<td>{{$item->date}}</td>
				<td>{{$item->date_time}}</td>
				<td>{{$item->time}}</td>
				<td>{{$item->FabricationCountry->description}}</td>
				<td>
					<a style='float: left;' href="{{route('editProduct', $item->id)}}" class='btn btn-warning' title='Edit'>E</a>
					<form title='Delete' method='post' action="{{route('deleteProduct', $item->id)}}">
						{!! method_field('DELETE') !!} {!! csrf_field() !!}
						<button class='btn btn-danger'> X </button>
					</form>
				</td>
			</tr>
			@endforeach
		</tbody>
	</table>
</div>
```

</details>

## Upcoming updates

Option | params | Description
------------ | -------- | -------------
--pagination | none | A boolean option which indicates the index view must have pagination.