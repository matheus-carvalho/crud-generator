# CRUD GENERATOR for Laravel
Crud Generator provides a complete experience of develop an entire CRUD only writing the migration file.

### Installing via Composer

```bash
composer require matheus-carvalho/crud-generator
```

### Publish the CSS and Config folder
```bash
php artisan vendor:publish
```

## Usage
1. Create your migration and fill up the fields.
2. Open a cmd on project root and type:
```bash
php artisan generate:crud table_name ResourceName
```

# Simple example
1. Creating the migration.
```bash 
php artisan make:migration create_categories_table
```

2. Filling up the migration.
```php
public function up()
{
    Schema::create('categories', function (Blueprint $table) {
        $table->id();

        $table->string('name');
        $table->text('description')->nullable();

        $table->timestamps();
    });
}
```

3. Generating the CRUD.
```bash
php artisan generate:crud categories Category
```

## Available column types example
1. Creating the migration.
```bash 
php artisan make:migration create_awesome_products_table
```

2. Filling up the migration.
```php
public function up()
{
    Schema::create('awesome_products', function (Blueprint $table) {
        $table->id();

        $table->string('name');
        $table->text('description');
        $table->double('price');
        $table->integer('quantity')->nullable();
        $table->dateTime('best_before')->nullable();
        $table->date('production_date')->nullable();
        $table->time('production_time')->nullable();
        $table->boolean('is_active');
        $table->foreignId('category_id')->constrained()->cascadeOnDelete();

        $table->timestamps();
    });
}
```

3. Generating the CRUD.
```bash
php artisan generate:crud awesome_products AwesomeProduct
```

### Tips

- The combination of `foreignId()` and `constrained()` methods is essential to make foreign keys work.
- The `cascadeOnDelete()` method is optional.
- Take note on snake_case naming pattern.

## Options

| Option     | params          | Description                                                      |
|------------|-----------------|------------------------------------------------------------------|
| table      | string          | Table name (snake_case).                                         |
| resource   | string          | Resource name (PascalCase) which will be used to name all files. |
| --style    | [default, none] | Specifies the style. Default = default.                          |
| --language | [br, en]        | Specifies the language. Default = en.                            |

## Configs

After you had published the css and config folders, you can navigate to config/crudgenerator.php and edit some configs, they are:

| Config              | Default | Description                                                                                                      |
|---------------------|---------|------------------------------------------------------------------------------------------------------------------|
| language            | en      | Specifies the language of texts inside the files. Accepts 'br' for 'Português brasileiro' or 'en' for 'English'. |
| style               | default | Specifies the style of views. Accepts 'default' for default css file or 'none' for raw html.                     |
| pagination_per_page | 5       | Specifies the number of elements should be rendered on each page at index's table. Accepts any positive value.   |

- After you edit any config, please be sure of run the `php artisan config:cache`
command to apply your changes.

## Output

After running the `php artisan generate:crud awesome_products AwesomeProduct` command, you'll be able to find these files in your project:

<details>
<summary> App\Http\Controllers\AwesomeProductController.php </summary>

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\AwesomeProductRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\AwesomeProduct;
use App\Models\Category;

class AwesomeProductController extends Controller
{
	public function index(): View
	{
		$perPage = 5;
		$items = AwesomeProduct::paginate($perPage);
		return view('awesome_product.index', compact('items'));
	}

	public function create(): View
	{
		$categoryList = Category::all();
		return view('awesome_product.create', compact('categoryList'));
	}

	public function edit($id): View
	{
		$item = AwesomeProduct::find($id);
		$categoryList = Category::all();
		return view('awesome_product.create', compact('categoryList', 'item'));
	}

	public function store(AwesomeProductRequest $request): RedirectResponse
	{
		$data = $request->validated();
		$insert = AwesomeProduct::create($data);
		if (!$insert) {
			return redirect()->back()->with('error', 'Error inserting AwesomeProduct');
		}

		return redirect()->route('indexAwesomeProduct')->with('message', 'AwesomeProduct inserted successfully');
	}

	public function update(AwesomeProductRequest $request, int $id): RedirectResponse
	{
		$data = $request->validated();

		$item = AwesomeProduct::find($id);
		$update = $item->update($data);
		if (!$update) {
			return redirect()->back();
		}

		return redirect()->route('indexAwesomeProduct');
	}

	public function destroy(int $id): RedirectResponse
	{
		$item = AwesomeProduct::find($id);
		$delete = $item->delete();
		if (!$delete) {
			return redirect()->back()->with('error', 'Error deleting AwesomeProduct');
		}

		return redirect()->route('indexAwesomeProduct')->with('message', 'AwesomeProduct deleted successfully');
	}
}
```

</details>

<details>
<summary> App\Models\AwesomeProduct.php </summary>

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @method static find($id)
 * @method static create(array $data)
 * @method static paginate(int $perPage)
 */
class AwesomeProduct extends Model
{
	protected $table = 'awesome_products';

	protected $fillable = [
		'name',
		'description',
		'price',
		'quantity',
		'best_before',
		'production_date',
		'production_time',
		'is_active',
		'category_id'
	];

	public static $notNullableBooleans = [
		'is_active'
	];

	public function Category(): BelongsTo
	{
		return $this->belongsTo('App\Models\Category', 'category_id', 'id');
	}
}
```

</details>

<details>
<summary> App\Http\Requests\AwesomeProductRequest.php </summary>

```php
<?php

namespace App\Http\Requests;

use App\Models\AwesomeProduct;
use Illuminate\Foundation\Http\FormRequest;

class AwesomeProductRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 *
	 * @return bool
	 */
	public function authorize(): bool
	{
		return true;
	}

	public function validationData(): array
	{
		$data = parent::validationData();

		foreach (AwesomeProduct::$notNullableBooleans as $notNullableBoolean) {
			$data[$notNullableBoolean] = $data[$notNullableBoolean] ?? false;
		}

		return $data;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array
	 */
	public function rules(): array
	{
		return [
			'name' => 'required',
			'description' => 'required',
			'price' => 'required',
			'is_active' => 'required',
			'quantity' => 'nullable',
			'best_before' => 'nullable',
			'production_date' => 'nullable',
			'production_time' => 'nullable',
			'category_id' => 'required|integer|min:1'
		];
	}

	public function messages(): array
	{
		return [
			'required' => 'The :attribute field is required.',
			'min' => 'You must select the :attribute.'
		];
	}
}
```

</details>

<details>
<summary> routes\web.php </summary>

```php
[...]
use App\Http\Controllers\AwesomeProductController;
[...]
Route::get('/awesome-products', [AwesomeProductController::class, 'index'])->name('awesome_products.index');
Route::get('/awesome-products/create', [AwesomeProductController::class, 'create'])->name('awesome_products.create');
Route::get('/awesome-products/edit/{id}', [AwesomeProductController::class, 'edit'])->name('awesome_products.edit');
Route::post('/awesome-products', [AwesomeProductController::class, 'store'])->name('awesome_products.store');
Route::put('/awesome-products/{id}', [AwesomeProductController::class, 'update'])->name('awesome_products.update');
Route::delete('/awesome-products/{id}', [AwesomeProductController::class, 'destroy'])->name('awesome_products.delete');
```

</details>

<details>
<summary> resources\views\awesome_product\create.blade.php </summary>

```php
<link href="{{asset('css/crudgenerator/default.css')}}" rel='stylesheet'>

<title>Create AwesomeProduct</title>

<div class="container">
	<div class="mt-20">
		<ul class="breadcrumb">
			<li><a href="{{ route('awesome_products.index') }}">AwesomeProduct</a></li>
			<li class='active'>Create AwesomeProduct</li>
		</ul>
	</div>
	<form method="post" 
		@if(isset($item))
			action="{{ route('awesome_products.update', $item->id) }}">
			{!! method_field('PUT') !!}
		@else
			action="{{ route('awesome_products.store') }}">
		@endif
		{!! csrf_field() !!}
		<div class="form-group">
			<label for="name">Name</label>
			<input class="form-control" id="name" name="name" value="{{isset($item) ? $item->name : old('name')}}" type="text">
			@error('name')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>
		<div class="form-group">
			<label for="description">Description</label>
			<textarea class="form-control" id="description" name="description">{{isset($item) ? $item->description : old('description')}}</textarea>
			@error('description')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>
		<div class="form-group">
			<label for="price">Price</label>
			<input class="form-control" id="price" name="price" value="{{isset($item) ? $item->price : old('price')}}" type="number" step="0.01">
			@error('price')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>
		<div class="form-group">
			<label for="quantity">Quantity</label>
			<input class="form-control" id="quantity" name="quantity" value="{{isset($item) ? $item->quantity : old('quantity')}}" type="number">
			@error('quantity')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>
		<div class="form-group">
			<label for="best_before">Best before</label>
			<input class="form-control" id="best_before" name="best_before" value="{{isset($item) ? str_replace(' ', 'T', $item->best_before) : old('best_before')}}" type="datetime-local">
			@error('best_before')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>
		<div class="form-group">
			<label for="production_date">Production date</label>
			<input class="form-control" id="production_date" name="production_date" value="{{isset($item) ? $item->production_date : old('production_date')}}" type="date">
			@error('production_date')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>
		<div class="form-group">
			<label for="production_time">Production time</label>
			<input class="form-control" id="production_time" name="production_time" value="{{isset($item) ? $item->production_time : old('production_time')}}" type="time">
			@error('production_time')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>
		<div class="form-group">
			<label for="is_active">Is active</label>
			<input class="form-check-input" id="is_active" name="is_active" type="checkbox" value="true"
				@if(isset($item) && $item->is_active)
					checked
				@endif
			>
			@error('is_active')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>
		<div class="form-group">
			<label for="category_id">Category</label>
			
			<select name="category_id" id="category_id" class="form-control">
				<option value="0">Select the Category</option>
				@foreach($categoryList as $category)
					<option value="{{$category->id}}"
						@if((isset($item) && $category->id == $item->category_id)||$category->id == old('category_id')) selected @endif
					>
						{{$category->name}}
					</option>
				@endforeach
			</select>
			@error('category_id')
				<div class="alert alert-danger">{{ $message }}</div>
			@enderror
		</div>

		<button class="btn btn-success">Save</button>
	</form>
</div>
```

</details>

<details>
<summary> resources\views\awesome_product\index.blade.php </summary>

```php
<link href="{{asset('css/crudgenerator/default.css')}}" rel='stylesheet'>

<title>AwesomeProduct</title>

<div class="container">
	<div class="row justify-content-around align-items-center mt-20">
		<div>
			<p class="list-header">AwesomeProduct List</p>
		</div>
		<div>
			<a href="{{route('awesome_products.create')}}" class="btn btn-success">New &#10004;</a>
		</div>
	</div>
	<div class="row">

	@if (session('message'))
		<div class='alert alert-success w-100'>
			{{ session('message') }}
		</div>
	@endif
	</div>

	<div class="row overflow-auto">
		<table class="list-table table-stripped mt-20 w-100">
			<thead>
				<tr>
					<th>Name</th>
					<th>Description</th>
					<th>Price</th>
					<th>Quantity</th>
					<th>Best before</th>
					<th>Production date</th>
					<th>Production time</th>
					<th>Is active</th>
					<th>Category</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
			@forelse ($items as $item)
				<tr>
					<td>{{$item->name}}</td>
					<td>{{$item->description}}</td>
					<td>{{$item->price}}</td>
					<td>{{$item->quantity}}</td>
					<td>{{$item->best_before}}</td>
					<td>{{$item->production_date}}</td>
					<td>{{$item->production_time}}</td>
					<td>{{$item->is_active}}</td>
					<td>{{$item->Category->name}}</td>
					<td class="row justify-content-start align-items-center">
						<div class="action-button">
							<a href="{{route('awesome_products.edit', $item->id)}}" class="btn btn-warning" title="Edit"> &#9998; </a>
						</div>
						<div class="action-button">
							<form title="Delete" method="post" action="{{route('awesome_products.delete', $item->id)}}">
								{!! method_field('DELETE') !!} {!! csrf_field() !!}
								<button class="btn btn-danger"> &times; </button>
							</form>
						</div>
					</td>
				</tr>
			@empty <tr> <td colspan="100%">No AwesomeProduct found!</td> </tr>
			@endforelse
			</tbody>
		</table>
		{{$items->links('pagination.crudgenerator')}}
	</div>
</div>
```

</details>

<details>
<summary> resources\views\pagination\crudgenerator.blade.php </summary>

```php
@if ($paginator->hasPages())
	<ul class="pager w-100">
		@if ($paginator->onFirstPage())
			<li class="disabled"><span>← Previous</span></li>
		@else
			<li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">← Previous</a></li>
		@endif

		@foreach ($elements as $element)
			@if (is_string($element))
				<li class="disabled"><span>{{ $element }}</span></li>
			@endif

			@if (is_array($element))
				@foreach ($element as $page => $url)
					@if ($page == $paginator->currentPage())
						<li class="active my-active"><span>{{ $page }}</span></li>
					@else
						<li><a href="{{ $url }}">{{ $page }}</a></li>
					@endif
				@endforeach
			@endif
		@endforeach

		@if ($paginator->hasMorePages())
			<li><a href="{{ $paginator->nextPageUrl() }}" rel="next">Next →</a></li>
		@else
			<li class="disabled"><span>Next →</span></li>
		@endif
	</ul>
	<small class="pager-info">Showing {{$paginator->firstItem()}} to {{$paginator->lastItem()}} of {{$paginator->total()}} results</small>
@endif
```

</details>
