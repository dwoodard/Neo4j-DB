<?php

use App\Models\Product;

describe('Product Model', function () {
    it('can create a Product', function () {
        $model = Product::create([
            'name' => 'Test Product',
            // Add other required attributes
        ]);

        expect($model->toArray())->toHaveKey('name');
        expect($model->name)->toBe('Test Product');
    });

    it('can find a Product by id', function () {
        $model = Product::create([
            'name' => 'Findable Product',
        ]);

        $found = Product::find($model->getId());

        expect($found)->not()->toBeNull();
        expect($found->name)->toBe('Findable Product');
    });

    it('can update a Product', function () {
        $model = Product::create([
            'name' => 'Original Name',
        ]);

        $model->name = 'Updated Name';
        $model->save();

        $updated = Product::find($model->getId());
        expect($updated->name)->toBe('Updated Name');
    });

    it('can delete a Product', function () {
        $model = Product::create([
            'name' => 'To Be Deleted',
        ]);

        $id = $model->getId();
        $model->delete();

        $deleted = Product::find($id);
        expect($deleted)->toBeNull();
    });

    it('can query Product records', function () {
        Product::create(['name' => 'First Product']);
        Product::create(['name' => 'Second Product']);

        $all = Product::all();
        expect($all->count())->toBeGreaterThanOrEqual(2);

        $filtered = Product::where('name', 'CONTAINS', 'First')->get();
        expect($filtered->count())->toBeGreaterThanOrEqual(1);
    });
});