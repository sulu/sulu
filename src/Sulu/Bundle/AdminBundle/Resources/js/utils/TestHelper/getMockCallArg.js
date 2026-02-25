// @flow

export default function getMockCallArg(mockFn: any, callIndex: number = -1, argIndex: number = 0): any {
    if (!mockFn || !mockFn.mock) {
        throw new Error('Expected a jest mock function');
    }

    const {calls} = mockFn.mock;
    if (!calls.length) {
        throw new Error('Expected mock to have been called at least once');
    }

    const resolvedCallIndex = callIndex < 0 ? calls.length + callIndex : callIndex;
    if (resolvedCallIndex < 0 || resolvedCallIndex >= calls.length) {
        throw new Error('Call index "' + callIndex + '" does not exist');
    }

    const call = calls[resolvedCallIndex];

    if (argIndex < 0 || argIndex >= call.length) {
        throw new Error('Argument index "' + argIndex + '" does not exist on selected mock call');
    }

    return call[argIndex];
}
