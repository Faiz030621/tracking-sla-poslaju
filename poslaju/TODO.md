# Bulk Import Optimization & Bug Fixes

## Tasks
- [x] Keep max batch size at 250 with parallel processing for speed
- [x] Implement parallel HTTP requests using curl_multi in batches of 20 concurrent
- [x] Add processTrackingRequestsParallel function with batch processing
- [x] Update time limits for parallel processing (10 minutes for 250 items)
- [x] Add small delays between batches to respect API limits
- [x] Fix function redeclaration errors by wrapping functions with function_exists
- [x] Change require to require_once for config.php in api files
- [x] Test the bulk import with parallel batch processing
