// @flow

export default function findMockCallArg(
    mockFn: any,
    predicate: (call: Array<any>, callIndex: number) => boolean,
    argIndex: number = 0
): any {
    if (!mockFn || !mockFn.mock) {
        throw new Error('Expected a jest mock function');
    }

    const {calls} = mockFn.mock;
    if (!calls.length) {
        throw new Error('Expected mock to have been called at least once');
    }

    for (let callIndex = calls.length - 1; callIndex >= 0; callIndex--) {
        const call = calls[callIndex];
        if (!predicate(call, callIndex)) {
            continue;
        }

        if (argIndex < 0 || argIndex >= call.length) {
            throw new Error('Argument index "' + argIndex + '" does not exist on selected mock call');
        }

        return call[argIndex];
    }

    throw new Error('No mock call matched the predicate');
}
