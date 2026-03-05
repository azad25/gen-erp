<?php

namespace Tests\Unit\Domain\HR\DTOs;

use Tests\TestCase;
use App\Domain\HR\DTOs\LogTimeData;
use Carbon\Carbon;

class LogTimeDataTest extends TestCase
{
    public function test_can_create_log_time_data_with_required_fields()
    {
        $data = new LogTimeData(
            employeeId: 1,
            entryDate: Carbon::parse('2026-03-04'),
            startTime: '09:00',
            endTime: '17:00',
            hours: 8.0,
            description: 'Working on project'
        );

        $this->assertEquals(1, $data->employeeId);
        $this->assertEquals('2026-03-04', $data->entryDate->toDateString());
        $this->assertEquals('09:00', $data->startTime);
        $this->assertEquals('17:00', $data->endTime);
        $this->assertEquals(8.0, $data->hours);
        $this->assertEquals('Working on project', $data->description);
    }

    public function test_can_create_log_time_data_with_all_fields()
    {
        $data = new LogTimeData(
            employeeId: 1,
            entryDate: Carbon::parse('2026-03-04'),
            startTime: '09:00',
            endTime: '17:00',
            hours: 8.0,
            taskId: 2,
            projectId: 3,
            description: 'Working on feature development',
            entryType: 'work',
            isBillable: true
        );

        $this->assertEquals(1, $data->employeeId);
        $this->assertEquals(2, $data->taskId);
        $this->assertEquals(3, $data->projectId);
        $this->assertEquals('2026-03-04', $data->entryDate->toDateString());
        $this->assertEquals('09:00', $data->startTime);
        $this->assertEquals('17:00', $data->endTime);
        $this->assertEquals(8.0, $data->hours);
        $this->assertEquals('Working on feature development', $data->description);
        $this->assertEquals('work', $data->entryType);
        $this->assertTrue($data->isBillable);
    }

    public function test_has_default_values()
    {
        $data = new LogTimeData(
            employeeId: 1,
            entryDate: Carbon::parse('2026-03-04'),
            startTime: '09:00',
            endTime: '17:00',
            hours: 8.0
        );

        $this->assertNull($data->taskId);
        $this->assertNull($data->projectId);
        $this->assertNull($data->description);
        $this->assertEquals('task', $data->entryType); // Changed to match enum
        $this->assertTrue($data->isBillable);
    }

    public function test_can_convert_to_array()
    {
        $data = new LogTimeData(
            employeeId: 1,
            entryDate: Carbon::parse('2026-03-04'),
            startTime: '09:00',
            endTime: '17:00',
            hours: 8.0,
            taskId: 2,
            projectId: 3,
            description: 'Working on project',
            entryType: 'meeting',
            isBillable: false
        );

        $array = $data->toArray();

        $expected = [
            'employee_id' => 1,
            'entry_date' => '2026-03-04',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'hours' => 8.0,
            'task_id' => 2,
            'project_id' => 3,
            'description' => 'Working on project',
            'entry_type' => 'meeting',
            'is_billable' => false
        ];

        $this->assertEquals($expected, $array);
    }

    public function test_validates_entry_type()
    {
        $validTypes = ['work', 'meeting', 'training', 'research', 'documentation'];

        foreach ($validTypes as $type) {
            $data = new LogTimeData(
                employeeId: 1,
                entryDate: Carbon::parse('2026-03-04'),
                startTime: '09:00',
                endTime: '17:00',
                hours: 8.0,
                description: 'Test',
                entryType: $type
            );

            $this->assertEquals($type, $data->entryType);
        }
    }

    public function test_can_create_from_array()
    {
        $array = [
            'employee_id' => 1,
            'entry_date' => '2026-03-04',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'hours' => 8.0,
            'task_id' => 2,
            'project_id' => 3,
            'description' => 'Working on feature',
            'entry_type' => 'work',
            'is_billable' => true
        ];

        $data = LogTimeData::fromArray($array);

        $this->assertEquals(1, $data->employeeId);
        $this->assertEquals(2, $data->taskId);
        $this->assertEquals(3, $data->projectId);
        $this->assertEquals('2026-03-04', $data->entryDate->toDateString());
        $this->assertEquals('09:00', $data->startTime);
        $this->assertEquals('17:00', $data->endTime);
        $this->assertEquals(8.0, $data->hours);
        $this->assertEquals('Working on feature', $data->description);
        $this->assertEquals('work', $data->entryType);
        $this->assertTrue($data->isBillable);
    }
}