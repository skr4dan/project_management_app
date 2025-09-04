## General
- Prefer modern Laravel 12 and PHP 8.4 conventions
- If there are multiple solutions, suggest the cleanest and most maintainable one.
- If something is unclear, ask for clarification instead of assuming.
- Highlight mistakes, anti-patterns, or possible improvements.

## Code Style
- Add typehints using php 8.4 features everywhere possible, use phpstan when it's not possible with native types.
- Avoid unnecessary comments - the code should be self-explanatory, the names of functions and variable especially.
- Don't use temporary variables unless they are used in some sort of a complicated expression later.

## Architecture
- Apply SOLID and OOP principles in design choices.
- Use service layer and repository pattern (with filters as separate criteria objects).
- Every service should be highly specified, don't make services like OrderService - named after a model and is a god class.
