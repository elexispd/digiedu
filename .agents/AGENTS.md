
- When eager loading relationships (e.g. using with()), always specify only the most important columns to avoid loading unnecessary data. For example, for Users, only load id and 
ame (e.g. with('coordinator:id,name')). Avoid loading created_at, status, etc., unless explicitly required.
