<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Learning\ProgressSummary;
use App\Enums\ContentStatus;
use App\Models\Certification;
use App\Models\Chapter;
use App\Models\Enrollment;
use App\Models\Part;
use App\Models\Section;
use App\Models\SectionProgress;
use App\Services\ProgressAggregatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProgressAggregatorServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgressAggregatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProgressAggregatorService();
    }

    public function test_summarize_progress_calculates_correct_ratios(): void
    {
        // 1. テストデータのセットアップ (Part 2件, 各Chapter 1件, 各Section 2件 = 計4 Sections)
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->create(['certification_id' => $certification->id]);

        $part1 = Part::factory()->create(['certification_id' => $certification->id, 'status' => ContentStatus::Published]);
        $chapter1 = Chapter::factory()->create(['part_id' => $part1->id, 'status' => ContentStatus::Published]);
        $section1 = Section::factory()->create(['chapter_id' => $chapter1->id, 'status' => ContentStatus::Published]);
        $section2 = Section::factory()->create(['chapter_id' => $chapter1->id, 'status' => ContentStatus::Published]);

        $part2 = Part::factory()->create(['certification_id' => $certification->id, 'status' => ContentStatus::Published]);
        $chapter2 = Chapter::factory()->create(['part_id' => $part2->id, 'status' => ContentStatus::Published]);
        $section3 = Section::factory()->create(['chapter_id' => $chapter2->id, 'status' => ContentStatus::Published]);
        $section4 = Section::factory()->create(['chapter_id' => $chapter2->id, 'status' => ContentStatus::Published]);

        // Chapter 1 配下の 2 Section を完了 (Chapter 1 / Part 1 完了)
        SectionProgress::factory()->create(['enrollment_id' => $enrollment->id, 'section_id' => $section1->id]);
        SectionProgress::factory()->create(['enrollment_id' => $enrollment->id, 'section_id' => $section2->id]);

        // 2. 実行
        $result = $this->service->summarizeProgress($enrollment);

        // 3. 検証
        $this->assertInstanceOf(ProgressSummary::class, $result);
        
        // Sections: 2 / 4 = 0.5 (50%)
        $this->assertSame(4, $result->sectionsTotal);
        $this->assertSame(2, $result->sectionsCompleted);
        $this->assertSame(0.5, $result->sectionCompletionRatio);

        // Chapters: 1 / 2 = 0.5 (50%)
        $this->assertSame(2, $result->chaptersTotal);
        $this->assertSame(1, $result->chaptersCompleted);
        $this->assertSame(0.5, $result->chapterCompletionRatio);

        // Parts: 1 / 2 = 0.5 (50%)
        $this->assertSame(2, $result->partsTotal);
        $this->assertSame(1, $result->partsCompleted);
        $this->assertSame(0.5, $result->partCompletionRatio);

        // Overall: sectionCompletionRatio と一致
        $this->assertSame(0.5, $result->overallCompletionRatio);
    }

    public function test_summarize_progress_ignores_unpublished_contents(): void
    {
        $certification = Certification::factory()->create();
        $enrollment = Enrollment::factory()->create(['certification_id' => $certification->id]);

        $part = Part::factory()->create(['certification_id' => $certification->id, 'status' => ContentStatus::Published]);
        $chapter = Chapter::factory()->create(['part_id' => $part->id, 'status' => ContentStatus::Published]);
        
        // 公開・非公開の Section を作成
        $publishedSection = Section::factory()->create(['chapter_id' => $chapter->id, 'status' => ContentStatus::Published]);
        $draftSection = Section::factory()->create(['chapter_id' => $chapter->id, 'status' => ContentStatus::Draft]);

        // 両方進捗を入れても、集計対象は公開済みの 1 件のみ
        SectionProgress::factory()->create(['enrollment_id' => $enrollment->id, 'section_id' => $publishedSection->id]);
        SectionProgress::factory()->create(['enrollment_id' => $enrollment->id, 'section_id' => $draftSection->id]);

        $result = $this->service->summarizeProgress($enrollment);

        $this->assertSame(1, $result->sectionsTotal);
        $this->assertSame(1, $result->sectionsCompleted);
        $this->assertSame(1.0, $result->sectionCompletionRatio);
    }

    public function test_batch_calculate_progress_calculates_multiple_enrollments_correctly(): void
    {
        $certification = Certification::factory()->create();

        $part = Part::factory()->create(['certification_id' => $certification->id, 'status' => ContentStatus::Published]);
        $chapter = Chapter::factory()->create(['part_id' => $part->id, 'status' => ContentStatus::Published]);
        $section1 = Section::factory()->create(['chapter_id' => $chapter->id, 'status' => ContentStatus::Published]);
        $section2 = Section::factory()->create(['chapter_id' => $chapter->id, 'status' => ContentStatus::Published]);

        $enrollment1 = Enrollment::factory()->create(['certification_id' => $certification->id]);
        $enrollment2 = Enrollment::factory()->create(['certification_id' => $certification->id]);

        // enrollment1: 1/2 完了 (0.5)
        SectionProgress::factory()->create(['enrollment_id' => $enrollment1->id, 'section_id' => $section1->id]);

        // enrollment2: 0/2 完了 (0.0)

        $enrollments = Enrollment::whereIn('id', [$enrollment1->id, $enrollment2->id])->get();
        $result = $this->service->batchCalculateProgress($enrollments);

        $this->assertCount(2, $result);
        $this->assertSame(0.5, $result[(string) $enrollment1->id]);
        $this->assertSame(0.0, $result[(string) $enrollment2->id]);
    }

    public function test_batch_calculate_progress_returns_empty_array_when_enrollments_empty(): void
    {
        $result = $this->service->batchCalculateProgress(Enrollment::query()->whereRaw('1 = 0')->get());
        $this->assertSame([], $result);
    }
}