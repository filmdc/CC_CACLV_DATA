 The issue was a field name mismatch between the database schema and the API resources:

  1. Database columns:
    - Funders table has funder_name (not name)
    - Programs table has program_name (not name)
    - Neither table has a description column
  2. API Resources were trying to access:
    - $this->name instead of $this->funder_name
    - $this->name instead of $this->program_name
    - $this->description which doesn't exist
  3. Fixed by:
    - Updated FunderResource to use $this->funder_name
    - Updated ProgramResource to use $this->program_name
    - Set description to null since the column doesn't exist

  The other Claude was seeing null values because PHP returns null when accessing non-existent properties. Now the
  API should return the actual funder and program names correctly.

  Tell the other Claude instance:
  - The issue was field name mismatches in the API resources
  - Funders and Programs now return their actual names from the database
  - The description field will always be null as it doesn't exist in the database
  - They should re-run their sync and all funders/programs should sync successfully now
