// @flow

function createPromiseMock(response: Promise<mixed>): any {
    const mockFn: any = jest.fn();
    mockFn.mockReturnValue(response);

    return mockFn;
}

function createMetadataStoreMock(schema?: Object = {}, jsonSchema?: Object = {}, schemaTypes?: mixed = null) {
    return {
        getJsonSchema: createPromiseMock(Promise.resolve(jsonSchema)),
        getSchema: createPromiseMock(Promise.resolve(schema)),
        getSchemaTypes: createPromiseMock(Promise.resolve(schemaTypes)),
    };
}

function createResourceRequesterMock(response?: mixed = {}) {
    const resolvedResponse = Promise.resolve(response);

    return {
        delete: createPromiseMock(resolvedResponse),
        deleteList: createPromiseMock(resolvedResponse),
        get: createPromiseMock(resolvedResponse),
        getList: createPromiseMock(resolvedResponse),
        patch: createPromiseMock(resolvedResponse),
        post: createPromiseMock(resolvedResponse),
        put: createPromiseMock(resolvedResponse),
    };
}

export {
    createMetadataStoreMock,
    createResourceRequesterMock,
};
