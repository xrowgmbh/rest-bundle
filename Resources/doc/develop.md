# Notes for developers

## Exceptions

If you want an error message to be returned to the API client, throw the `UserException` exception.

    use xrow\restBundle\Exception\UserException;

    ...

    throw(new UserException('My error has occurred'));

The error message and stack trace will also be logged.

Take care not to expose secure information to the client!
