# Meilisearch Repository Pattern Implementation in Laravel

## Purpose
This section demonstrates how to implement the Repository pattern for interacting with Meilisearch within a Laravel application. This approach aims to provide a cleaner and more maintainable way to handle search operations, similar to how repositories are used with Eloquent models.

### Key Features
This package extends the functionality for interacting with Meilisearch in your Laravel applications, offering the following key features:

1.  **Enhanced Scout Builder:**
    * Introduced powerful query constraints directly within the Scout Builder, including:
        * `whereNull()`: Filter results where a specific field is `null`.
        * `whereBetween()`: Filter results where a specific field's value falls within a given range.
        * Support for Nested `where` Clauses: Enables more complex and grouped filtering logic.

2.  **Repository Criteria:**
    * Implements the Criteria pattern within the repository, allowing you to encapsulate and reuse query constraints. This promotes cleaner and more modular data retrieval logic. You can define specific criteria classes to apply common filtering, ordering, or pagination rules to your Meilisearch queries.

3.  **Transformers:**
    * Provides a mechanism for transforming the data retrieved from Meilisearch before it's returned to your application. This allows you to format the search results according to your specific needs, ensuring consistency and simplifying data presentation.

## Uses

Let's consider a Laravel model named `User` that is being indexed into Meilisearch to enhance search performance.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class User extends Model
{
    use Searchable;
}
```

To utilize the Repository pattern, similar to the l5-repository for Eloquent, you would first define an interface for your repository:
```php
<?php

namespace App\Repositories\Interfaces;

use JoBins\Meilisearch\Interfaces\RepositoryInterface;

interface UserRepositoryInterface extends RepositoryInterface
{
    // You can define specific methods for your User repository here if needed
}
```
Next, create the repository implementation:

```php
<?php

namespace App\Repositories;

use App\Models\User;
use JoBins\Meilisearch\Meilisearch\Builder;
use JoBins\Meilisearch\Repository;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository extends Repository implements UserRepositoryInterface
{
    public function builder(): Builder {
        return User::search();
    }
}
```

Finally, you need to bind the interface to its implementation in a Service Provider (e.g., AppServiceProvider):

```php
<?php

namespace App\Providers;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    public function boot()
    {
        //
    }
}
```

## Consuming in a Controller
Once the repository and its binding are set up, you can easily consume it within your Laravel controllers:

```php
<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {
    }

    public function index(): JsonResponse
    {
        $results = $this->userRepository->get();

        return response()->json($results);
    }
}
```
