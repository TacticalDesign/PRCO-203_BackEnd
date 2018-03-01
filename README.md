# PRCO-203_BackEnd

## API Calls

### Current Challenges

**Viewing all challenges:**  
```
/GetChallenges.php?find=all
```
- Returns all current challenges

**Viewing a challenge by its ID:**  
```
/GetChallenges.php?find=3456
```
- Returns the requested challenge

**Viewing some challenges by their IDs:**  
```
/GetChallenges.php?find=3456,7643,3564
```
- Returns the requested challenges

**Adding a new challenge:**  
```
/GetChallenges.php?prop1=value1&prop2=value2
```
- Any values apart from the ID of a challenge can be instantiated 
- Multiple values can be instantiated by seperating them with a `&`
- There is no set order to the values
- Returns the newly created item

**Editing a challenge by its ID:**  
```
/GetChallenges.php?edit=3456&prop1=value1&prop2=value2
```
- Any valid value apart from the ID of a challenge can be edited
- Multiple values can be edited by seperating them with a `&`
- There is no set order to the values
- If the edit is successful then it returns the edited challenge
- If the edit is not successful then it returns `false`

**Deleting a challenge by its ID:**  
```
/GetChallenges.php?delete=3456
```
- If deletion is successful then it returns the now removed challenge
- If deletion is not successful then it returns `false`

**Finding challenges by a search term:**  
```
/GetChallenges.php?search=query1
```
- Finds all challenges matching the search query
- A challenge needs at least two consecutive characters to match
- Returned challenges are ordered with the highest relevance first
- Challenges can match with: name, description, skills, and location

**Finding challenges by a search term and exact values:**  
```
/GetChallenges.php?search=query1&where=reward:500;name:ExampleName
```
- Finds all challenges matching the search query
- Any specified values must also match exactly
- Exact values are seperated from their names with colons
- Multiple exact values can be given, seperated by semi-colons
- Will error if empty exact values or names are given
- Will error if there is a trailing colon or semi-colon

### Young People

**Viewing all young people:**  
```
/GetYoungPeople.php?find=all
```
- Returns all young people

**Viewing a young person by their ID:**  
```
/GetYoungPeople.php?find=3456
```
- Returns the requested young person

**Viewing some young people by their IDs:**  
```
/GetYoungPeople.php?find=3456,7643,3564
```
- Returns the requested young people

**Adding a new young person:**  
```
/GetYoungPeople.php?prop1=value1&prop2=value2
```
- Any values apart from the ID of a young person can be instantiated 
- Multiple values can be instantiated by seperating them with a `&`
- There is no set order to the values
- Returns the newly created item

**Editing a young person by their ID:**  
```
/GetYoungPeople.php?edit=3456&prop1=value1&prop2=value2
```
- Any valid value apart from the ID of a young person can be edited
- Multiple values can be edited by seperating them with a `&`
- There is no set order to the values
- If the edit is successful then it returns the edited young person
- If the edit is not successful then it returns `false`

**Deleting a young person by their ID:**  
```
/GetYoungPeople.php?delete=3456
```
- If deletion is successful then it returns the now removed challenge
- If deletion is not successful then it returns `false`

**Finding young people by a search term:**  
```
/GetYoungPeople.php?search=query1
```
- Finds all young people matching the search query
- A young person needs at least two consecutive characters to match
- Returned young people are ordered with the highest relevance first
- Young people can match with: first name, surname, skills, interests

**Finding young people by a search term and exact values:**  
```
/GetYoungPeople.php?search=query1&where=firstName:Toby;surname:Smith
```
- Finds all young people matching the search query
- Any specified values must also match exactly
- Exact values are seperated from their names with colons
- Multiple exact values can be given, seperated by semi-colons
- Will error if empty exact values or names are given
- Will error if there is a trailing colon or semi-colon
