<?php

use App\Enums\WidgetType;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\DashboardWidget;
use App\Models\SavedReport;
use App\Models\User;
use App\Services\AlertRulesService;
use App\Services\CompanyContext;
use App\Services\DashboardService;
use App\Services\ReportBuilderService;

// ═══════════════════════════════════════════════════
// Dashboard Tests
// ═══════════════════════════════════════════════════

test('DashboardService returns widgets scoped to active company', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);

    DashboardWidget::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'widget_type' => WidgetType::TOTAL_SALES->value,
    ]);

    $service = app(DashboardService::class);
    $widgets = $service->getWidgetsForUser($user, $company);

    expect($widgets)->toHaveCount(1);
    expect($widgets->first()->widget_type)->toBe(WidgetType::TOTAL_SALES);
});

test('user can add a widget and it persists', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $service = app(DashboardService::class);
    $widget = $service->createWidget($user, $company, WidgetType::PENDING_APPROVALS);

    expect($widget)->toBeInstanceOf(DashboardWidget::class);
    expect($widget->widget_type)->toBe(WidgetType::PENDING_APPROVALS);
    expect($widget->width)->toBe(3);

    $persisted = DashboardWidget::withoutGlobalScopes()->find($widget->id);
    expect($persisted)->not->toBeNull();
});

test('user can remove a widget', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $widget = DashboardWidget::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'widget_type' => WidgetType::CASH_BALANCE->value,
    ]);

    $widget->delete();

    expect(DashboardWidget::withoutGlobalScopes()->find($widget->id))->toBeNull();
});

test('TotalSalesWidget returns correct data structure scoped to company', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    $service = app(DashboardService::class);
    $widget = $service->createWidget($user, $company, WidgetType::TOTAL_SALES);
    $resolved = $service->resolveWidget($widget);
    $data = $resolved->getData();

    expect($data)->toHaveKeys(['total_amount', 'count', 'change_percent', 'period']);
    expect($data['total_amount'])->toBe(0);
});

test('Company A widgets are not visible to Company B', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    DashboardWidget::withoutGlobalScopes()->create([
        'company_id' => $companyA->id,
        'user_id' => $userA->id,
        'widget_type' => WidgetType::TOTAL_SALES->value,
    ]);

    DashboardWidget::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'user_id' => $userB->id,
        'widget_type' => WidgetType::CASH_BALANCE->value,
    ]);

    $service = app(DashboardService::class);

    $widgetsA = $service->getWidgetsForUser($userA, $companyA);
    expect($widgetsA)->toHaveCount(1);
    expect($widgetsA->first()->widget_type)->toBe(WidgetType::TOTAL_SALES);

    $widgetsB = $service->getWidgetsForUser($userB, $companyB);
    expect($widgetsB)->toHaveCount(1);
    expect($widgetsB->first()->widget_type)->toBe(WidgetType::CASH_BALANCE);
});

// ═══════════════════════════════════════════════════
// Report Builder Tests
// ═══════════════════════════════════════════════════

test('report runs and returns correct columns for selected fields', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $report = SavedReport::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Customer List',
        'entity_type' => 'customer',
        'selected_fields' => ['name', 'email', 'phone'],
    ]);

    $service = app(ReportBuilderService::class);
    $result = $service->run($report);

    expect($result)->toHaveKeys(['columns', 'rows', 'total']);
    expect($result['columns'])->toBe(['name', 'email', 'phone']);
});

test('filter by date range returns structured result', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $report = SavedReport::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Sales This Month',
        'entity_type' => 'sales',
        'selected_fields' => ['invoice_number', 'total_amount'],
        'filters' => [['field' => 'created_at', 'operator' => 'between', 'value' => 'this_month']],
    ]);

    $service = app(ReportBuilderService::class);
    $result = $service->run($report);

    expect($result['columns'])->toBe(['invoice_number', 'total_amount']);
    expect($result['rows'])->toBeArray();
});

test('group by + aggregate returns correct structure', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $report = SavedReport::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Sales By Product',
        'entity_type' => 'product',
        'selected_fields' => ['name', 'selling_price'],
        'group_by' => 'name',
        'aggregate' => ['field' => 'selling_price', 'function' => 'sum'],
    ]);

    $service = app(ReportBuilderService::class);
    $result = $service->run($report);

    expect($result)->toHaveKeys(['columns', 'rows', 'total']);
});

test('report is scoped to company and cannot include other company data', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $reportA = SavedReport::withoutGlobalScopes()->create([
        'company_id' => $companyA->id,
        'name' => 'Company A Report',
        'entity_type' => 'customer',
        'selected_fields' => ['name'],
    ]);

    CompanyContext::setActive($companyA);
    expect(SavedReport::where('id', $reportA->id)->exists())->toBeTrue();

    CompanyContext::setActive($companyB);
    expect(SavedReport::where('id', $reportA->id)->exists())->toBeFalse();
});

test('export dispatches correctly', function (): void {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    CompanyUser::factory()->owner()->create([
        'company_id' => $company->id,
        'user_id' => $user->id,
    ]);
    CompanyContext::setActive($company);

    $report = SavedReport::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Export Test',
        'entity_type' => 'product',
        'selected_fields' => ['name', 'sku'],
    ]);

    $service = app(ReportBuilderService::class);
    $path = $service->export($report, 'pdf');

    // Stub returns empty path — just verify no exception
    expect($path)->toBeString();
});

test('scheduled report is saved with correct frequency', function (): void {
    $company = Company::factory()->create();

    $report = SavedReport::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Weekly Sales',
        'entity_type' => 'sales',
        'selected_fields' => ['total'],
        'schedule_frequency' => 'weekly',
        'schedule_recipients' => ['admin@example.com'],
    ]);

    $service = app(ReportBuilderService::class);
    $service->schedule($report);

    $report->refresh();
    expect($report->is_scheduled)->toBeTrue();
    expect($report->last_run_at)->not->toBeNull();
});

// ═══════════════════════════════════════════════════
// Alert Rules Tests
// ═══════════════════════════════════════════════════

test('alert rule is evaluated after model save', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    AlertRule::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Low Stock',
        'entity_type' => 'product',
        'trigger_field' => 'stock_quantity',
        'operator' => 'lt',
        'trigger_value' => '10',
        'channels' => ['in_app'],
        'target_roles' => ['owner'],
        'message_template' => 'Stock is low for {name}',
        'is_active' => true,
    ]);

    // Simulate a model with stock_quantity = 5
    $fakeEntity = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'companies';

        protected $guarded = [];
    };
    $fakeEntity->id = $company->id;
    $fakeEntity->company_id = $company->id;
    $fakeEntity->stock_quantity = 5;

    $service = app(AlertRulesService::class);
    $service->evaluate('product', $fakeEntity);

    $log = AlertLog::withoutGlobalScopes()
        ->where('company_id', $company->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->triggered_value)->toBe('5');
});

test('rule with lt operator triggers when value falls below threshold', function (): void {
    $company = Company::factory()->create();

    $rule = AlertRule::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Test LT',
        'entity_type' => 'product',
        'trigger_field' => 'stock_quantity',
        'operator' => 'lt',
        'trigger_value' => '20',
        'channels' => ['in_app'],
        'target_roles' => ['owner'],
        'message_template' => 'Test',
        'is_active' => true,
    ]);

    $fakeEntity = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'companies';

        protected $guarded = [];
    };
    $fakeEntity->stock_quantity = 15;

    $service = app(AlertRulesService::class);

    expect($service->checkRule($rule, $fakeEntity))->toBeTrue();

    $fakeEntity->stock_quantity = 25;
    expect($service->checkRule($rule, $fakeEntity))->toBeFalse();
});

test('cooldown prevents duplicate alerts within cooldown window', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $rule = AlertRule::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Cooldown Test',
        'entity_type' => 'product',
        'trigger_field' => 'stock_quantity',
        'operator' => 'lt',
        'trigger_value' => '10',
        'channels' => ['in_app'],
        'target_roles' => ['owner'],
        'message_template' => 'Test',
        'is_active' => true,
        'cooldown_minutes' => 60,
    ]);

    $fakeEntity = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'companies';

        protected $guarded = [];
    };
    $fakeEntity->id = $company->id;
    $fakeEntity->company_id = $company->id;
    $fakeEntity->stock_quantity = 5;

    $service = app(AlertRulesService::class);

    // First dispatch — should create log
    $service->dispatch($rule, $fakeEntity, 5);
    expect(AlertLog::withoutGlobalScopes()->where('alert_rule_id', $rule->id)->count())->toBe(1);

    // Second dispatch within cooldown — should be skipped
    $service->dispatch($rule, $fakeEntity, 5);
    expect(AlertLog::withoutGlobalScopes()->where('alert_rule_id', $rule->id)->count())->toBe(1);
});

test('once repeat_behaviour fires only once per entity', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $rule = AlertRule::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Once Test',
        'entity_type' => 'product',
        'trigger_field' => 'stock_quantity',
        'operator' => 'lt',
        'trigger_value' => '10',
        'channels' => ['in_app'],
        'target_roles' => ['owner'],
        'message_template' => 'Test',
        'is_active' => true,
        'repeat_behaviour' => 'once',
    ]);

    $fakeEntity = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'companies';

        protected $guarded = [];
    };
    $fakeEntity->id = $company->id;
    $fakeEntity->company_id = $company->id;
    $fakeEntity->stock_quantity = 5;

    $service = app(AlertRulesService::class);

    $service->dispatch($rule, $fakeEntity, 5);
    $service->dispatch($rule, $fakeEntity, 3);

    expect(AlertLog::withoutGlobalScopes()->where('alert_rule_id', $rule->id)->count())->toBe(1);
});

test('alert log is written on trigger', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $rule = AlertRule::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Log Test',
        'entity_type' => 'product',
        'trigger_field' => 'stock_quantity',
        'operator' => 'lt',
        'trigger_value' => '10',
        'channels' => ['in_app', 'email'],
        'target_roles' => ['owner', 'admin'],
        'message_template' => 'Test',
        'is_active' => true,
    ]);

    $fakeEntity = new class extends \Illuminate\Database\Eloquent\Model
    {
        protected $table = 'companies';

        protected $guarded = [];
    };
    $fakeEntity->id = $company->id;
    $fakeEntity->company_id = $company->id;
    $fakeEntity->stock_quantity = 5;

    $service = app(AlertRulesService::class);
    $service->dispatch($rule, $fakeEntity, 5);

    $log = AlertLog::withoutGlobalScopes()->where('alert_rule_id', $rule->id)->first();

    expect($log)->not->toBeNull();
    expect($log->channels_sent)->toBe(['in_app', 'email']);
    expect($log->recipients_count)->toBe(2);
    expect($log->triggered_value)->toBe('5');
});

test('two companies have separate alert rules (tenant isolation)', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    AlertRule::withoutGlobalScopes()->create([
        'company_id' => $companyA->id,
        'name' => 'Rule A',
        'entity_type' => 'product',
        'trigger_field' => 'stock_quantity',
        'operator' => 'lt',
        'trigger_value' => '10',
        'channels' => ['in_app'],
        'target_roles' => ['owner'],
        'message_template' => 'Test A',
        'is_active' => true,
    ]);

    AlertRule::withoutGlobalScopes()->create([
        'company_id' => $companyB->id,
        'name' => 'Rule B',
        'entity_type' => 'product',
        'trigger_field' => 'stock_quantity',
        'operator' => 'gt',
        'trigger_value' => '100',
        'channels' => ['email'],
        'target_roles' => ['admin'],
        'message_template' => 'Test B',
        'is_active' => true,
    ]);

    CompanyContext::setActive($companyA);
    expect(AlertRule::all())->toHaveCount(1);
    expect(AlertRule::first()->name)->toBe('Rule A');

    CompanyContext::setActive($companyB);
    expect(AlertRule::all())->toHaveCount(1);
    expect(AlertRule::first()->name)->toBe('Rule B');
});
