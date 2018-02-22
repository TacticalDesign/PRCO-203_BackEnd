# PRCO-203_BackEnd

## API Calls

### Current Challenges

**Viewing all challenges:**  
```
/GetChallenges.php?find=all
```
- Returns all current challenges

**Viewing some challenges by their IDs:**  
```
/GetChallenges.php?find=3456,7643,3564
```
- Returns the requested challenges

**Viewing a challenge by its ID:**  
```
/GetChallenges.php?find=3456
```
- Returns the requested challenge

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
- Any values apart from the ID of a challenge can be instantiated 
- Multiple values can be instantiated by seperating them with a `&`
- There is no set order to the values
- If deletion is successful then it returns the now removed challenge
- If deletion is not successful then it returns `false`
