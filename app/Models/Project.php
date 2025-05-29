<?php

namespace App\Models;

use App\Graph\GraphModel;
use Illuminate\Support\Collection;

class Project extends GraphModel
{
    protected static string $label = 'Project';

    protected array $fillable = [
        'name',
        'description',
        'status',
        'priority',
        'start_date',
        'end_date',
        'deadline',
        'budget',
        'actual_cost',
        'progress',
        'technology_stack',
        'repository_url',
        'active',
    ];

    protected array $casts = [
        'budget' => 'float',
        'actual_cost' => 'float',
        'progress' => 'integer',
        'active' => 'boolean',
        'technology_stack' => 'array',
    ];

    /**
     * Get team members working on this project
     */
    public function getTeamMembers(): Collection
    {
        return $this->getRelated('WORKS_ON', 'in');
    }

    /**
     * Get project manager
     */
    public function getProjectManager(): ?Person
    {
        $managers = $this->getRelated('MANAGES', 'in');

        return $managers->first();
    }

    /**
     * Get project status info
     */
    public function getStatusInfo(): array
    {
        $progress = $this->getAttribute('progress') ?? 0;
        $status = $this->getAttribute('status') ?? 'Unknown';

        return [
            'status' => $status,
            'progress' => $progress,
            'is_completed' => $progress >= 100,
            'is_overdue' => $this->isOverdue(),
            'days_remaining' => $this->getDaysRemaining(),
            'budget_status' => $this->getBudgetStatus(),
        ];
    }

    /**
     * Check if project is overdue
     */
    public function isOverdue(): bool
    {
        $deadline = $this->getAttribute('deadline');
        if (! $deadline) {
            return false;
        }

        return now()->isAfter($deadline) && $this->getAttribute('progress') < 100;
    }

    /**
     * Get days remaining until deadline
     */
    public function getDaysRemaining(): ?int
    {
        $deadline = $this->getAttribute('deadline');
        if (! $deadline) {
            return null;
        }

        return now()->diffInDays($deadline, false);
    }

    /**
     * Get budget status
     */
    public function getBudgetStatus(): array
    {
        $budget = $this->getAttribute('budget') ?? 0;
        $actualCost = $this->getAttribute('actual_cost') ?? 0;

        return [
            'budget' => $budget,
            'actual_cost' => $actualCost,
            'remaining' => $budget - $actualCost,
            'over_budget' => $actualCost > $budget,
            'utilization_percentage' => $budget > 0 ? ($actualCost / $budget) * 100 : 0,
        ];
    }

    /**
     * Assign a person to this project
     */
    public function assignPerson(Person $person, array $details = []): bool
    {
        return $person->createRelationshipTo($this, 'WORKS_ON', $details);
    }

    /**
     * Set project manager
     */
    public function setManager(Person $person): bool
    {
        return $person->createRelationshipTo($this, 'MANAGES');
    }

    /**
     * Update project progress
     */
    public function updateProgress(int $progress): bool
    {
        $this->setAttribute('progress', max(0, min(100, $progress)));

        if ($progress >= 100) {
            $this->setAttribute('status', 'Completed');
            $this->setAttribute('end_date', now()->toDateString());
        }

        return $this->save();
    }

    /**
     * Scope for active projects
     */
    public static function active(): \App\Graph\GraphQueryBuilder
    {
        return static::where('active', '=', true);
    }

    /**
     * Scope for projects by status
     */
    public static function withStatus(string $status): \App\Graph\GraphQueryBuilder
    {
        return static::where('status', '=', $status);
    }

    /**
     * Scope for overdue projects
     */
    public static function overdue(): \App\Graph\GraphQueryBuilder
    {
        return static::where('deadline', '<', now()->toDateString())
            ->where('progress', '<', 100);
    }

    /**
     * Scope for projects by priority
     */
    public static function withPriority(string $priority): \App\Graph\GraphQueryBuilder
    {
        return static::where('priority', '=', $priority);
    }

    /**
     * Get project summary
     */
    public function getSummary(): array
    {
        $teamMembers = $this->getTeamMembers();
        $manager = $this->getProjectManager();

        return [
            'id' => $this->getId(),
            'name' => $this->getAttribute('name'),
            'status' => $this->getAttribute('status'),
            'progress' => $this->getAttribute('progress'),
            'priority' => $this->getAttribute('priority'),
            'team_size' => $teamMembers->count(),
            'manager' => $manager ? $manager->name : null,
            'budget_info' => $this->getBudgetStatus(),
            'is_overdue' => $this->isOverdue(),
            'days_remaining' => $this->getDaysRemaining(),
        ];
    }
}
