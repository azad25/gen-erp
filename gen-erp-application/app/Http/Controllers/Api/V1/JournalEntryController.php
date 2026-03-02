<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Journal Entries",
 *     description="Journal entry management"
 * )
 * REST API v1 controller for Journal Entry operations.
 */
class JournalEntryController extends BaseApiController
{
    public function __construct(
        private AccountingService $accountingService
    ) {}

    /**
     * @OA\Get(
     *     path="/journal-entries",
     *     summary="List all journal entries",
     *     tags={"Journal Entries"},
     *     @OA\Parameter(name="search", in="query", description="Search term", @OA\Schema(type="string")),
     *     @OA\Parameter(name="entry_date", in="query", description="Entry date", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", type="array", @OA\Items(allOf={@OA\Schema(ref="#/components/schemas/JournalEntry")})),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $entries = JournalEntry::query()
            ->where('company_id', activeCompany()->id)
            ->when($request->get('search'), fn ($q, $s) => $q->where('reference', 'LIKE', "%{$s}%"))
            ->when($request->get('entry_date'), fn ($q, $d) => $q->where('entry_date', $d))
            ->with(['lines.account'])
            ->orderBy('entry_date', 'desc')
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($entries);
    }

    /**
     * @OA\Get(
     *     path="/journal-entries/{id}",
     *     summary="Get a specific journal entry",
     *     tags={"Journal Entries"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Journal Entry ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/JournalEntry")
     *         )
     *     )
     * )
     */
    public function show(JournalEntry $journalEntry): JsonResponse
    {
        $journalEntry->load(['lines.account']);

        return $this->success($journalEntry);
    }

    /**
     * @OA\Post(
     *     path="/journal-entries",
     *     summary="Create a new journal entry",
     *     tags={"Journal Entries"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="entry_date", type="string", format="date"),
     *             @OA\Property(property="reference", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="lines", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Journal entry created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="data", ref="#/components/schemas/JournalEntry"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'reference' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'lines' => ['required', 'array'],
            'lines.*.account_id' => ['required', 'exists:accounts,id'],
            'lines.*.debit' => ['required', 'integer', 'min:0'],
            'lines.*.credit' => ['required', 'integer', 'min:0'],
        ]);

        $validated['company_id'] = activeCompany()->id;

        $entry = $this->accountingService->createJournalEntry($validated);

        return $this->success($entry->load(['lines.account']), 'Journal entry created', 201);
    }

    /**
     * @OA\Delete(
     *     path="/journal-entries/{id}",
     *     summary="Delete a journal entry",
     *     tags={"Journal Entries"},
     *     @OA\Parameter(name="id", in="path", required=true, description="Journal Entry ID", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Journal entry deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string")
     *         )
     *     )
     * )
     */
    public function destroy(JournalEntry $journalEntry): JsonResponse
    {
        $journalEntry->delete();

        return $this->success(null, 'Journal entry deleted');
    }
}
