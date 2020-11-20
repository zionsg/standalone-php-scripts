# Group CSV

Parse CSV, group by the first X columns and return CSV.

## Example
- Given an input CSV:

        "last_name","first_name","country"
        "alef","alpha","SG"
        "alef","alpha","MY"
        "alef","alep","CA"
        "beth","beta","GB"
        "beth","beta","US"

- It will look like the following when opened in Microsoft Excel:

        +-----------+------------+---------+
        | last_name | first_name | country |
        +-----------+------------+---------+
        | alef      | alpha      | SG      |
        | alef      | alpha      | MY      |
        | alef      | alep       | CA      |
        | beth      | beta       | GB      |
        | beth      | beta       | US      |
        +-----------+------------+---------+

- Grouping by the first 2 columns, the output CSV will be:

        "Combined Group: last_name,first_name","Group 1: last_name","Group 2: first_name",last_name,first_name,country
        combined:0:0,group:1:0,group:2:0,alef,alpha,SG
        combined:0:0,group:1:0,group:2:0,,,MY
        combined:0:1,group:1:0,group:2:1,,alep,CA
        combined:1:0,group:1:1,group:2:0,beth,beta,GB
        combined:1:0,group:1:1,group:2:0,,,US

- Which will look like the following when opened in Microsoft Excel:

        +--------------------------------------+--------------------+---------------------+-----------+------------+---------+
        | Combined Group: last_name,first_name | Group 1: last_name | Group 2: first_name | last_name | first_name | country |
        +--------------------------------------+--------------------+---------------------+-----------+------------+---------+
        | combined:0:0                         | group:1:0          | group:2:0           | alef      | alpha      | SG      |
        | combined:0:0                         | group:1:0          | group:2:0           |           |            | MY      |
        +--------------------------------------+--------------------+---------------------+-----------+------------+---------+
        | combined:0:1                         | group:1:0          | group:2:1           |           | alep       | CA      |
        +--------------------------------------+--------------------+---------------------+-----------+------------+---------+
        | combined:1:0                         | group:1:1          | group:2:0           | beth      | beta       | GB      |
        | combined:1:0                         | group:1:1          | group:2:0           |           |            | US      |
        +--------------------------------------+--------------------+---------------------+-----------+------------+---------+
