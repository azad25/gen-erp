/**
 * Map API pagination response to DataTable pagination format
 * 
 * API Response format:
 * {
 *   success: true,
 *   data: [...],
 *   meta: {
 *     current_page: 1,
 *     per_page: 15,
 *     total: 100,
 *     last_page: 7
 *   }
 * }
 * 
 * DataTable expects:
 * {
 *   current_page: 1,
 *   per_page: 15,
 *   total: 100,
 *   last_page: 7,
 *   from: 1,
 *   to: 15,
 *   links: []
 * }
 */
export function mapApiPagination(apiResponse) {
  const meta = apiResponse.meta || {}
  const currentPage = meta.current_page || 1
  const perPage = meta.per_page || 15
  const total = meta.total || 0
  
  return {
    current_page: currentPage,
    per_page: perPage,
    total: total,
    last_page: meta.last_page || 1,
    from: total > 0 ? (currentPage - 1) * perPage + 1 : 0,
    to: Math.min(currentPage * perPage, total),
    links: [] // Not used for API-based pagination
  }
}
