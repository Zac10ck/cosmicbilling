# Migration: Add Stock Count to Products

Run this SQL on your MySQL server before using the updated code.

```sql
ALTER TABLE products ADD COLUMN stock_count INT DEFAULT 0 AFTER unit;
```
