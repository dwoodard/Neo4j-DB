<?php

use App\Models\TestCategory;

describe('TestCategory Model', function () {
    it('can create a TestCategory', function () {
        $model = TestCategory::create([
            'name' => 'Test TestCategory',
            // Add other required attributes
        ]);

        expect($model->toArray())->toHaveKey('name');
        expect($model->name)->toBe('Test TestCategory');
    });

    it('can find a TestCategory by id', function () {
        $model = TestCategory::create([
            'name' => 'Findable TestCategory',
        ]);

        $found = TestCategory::find($model->getId());

        expect($found)->not()->toBeNull();
        expect($found->name)->toBe('Findable TestCategory');
    });

    it('can update a TestCategory', function () {
        $model = TestCategory::create([
            'name' => 'Original Name',
        ]);

        $model->name = 'Updated Name';
        $model->save();

        $updated = TestCategory::find($model->getId());
        expect($updated->name)->toBe('Updated Name');
    });

    it('can delete a TestCategory', function () {
        $model = TestCategory::create([
            'name' => 'To Be Deleted',
        ]);

        $id = $model->getId();
        $model->delete();

        $deleted = TestCategory::find($id);
        expect($deleted)->toBeNull();
    });

    it('can query TestCategory records', function () {
        TestCategory::create(['name' => 'First TestCategory']);
        TestCategory::create(['name' => 'Second TestCategory']);

        $all = TestCategory::all();
        expect($all->count())->toBeGreaterThanOrEqual(2);

        $filtered = TestCategory::where('name', 'CONTAINS', 'First')->get();
        expect($filtered->count())->toBeGreaterThanOrEqual(1);
    });
});