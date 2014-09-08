# Issues-Bitbucket

Simple tool which extract open/new issues from Bitbucket.

## TODO

- manage date format and french/english (regarding browser)
- get last comments, https://bitbucket.org/api/1.0/repositories/{accountname}/{repo_slug}/issues/{issue_id}/comments
- graphic interface with table and why not a way to send the report by mail or send a mail for a dedicated issue
- order by priority (critical, major, minor, trivial), type (bug, enhancement, task, proposal) and add color 

## Notes

- Alternative : http://cunneen.github.io/Bitbucket-Issue-Tracker-Visualizer/public_html/index.html
- Testing the Bitbucket API : http://restbrowser.bitbucket.org/
- Define in a config file all the Bitbucket data (ACCOUNTNAME, REPO_SLUG, USER, PWD)