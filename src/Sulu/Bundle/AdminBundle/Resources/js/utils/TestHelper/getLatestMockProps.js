// @flow

import getMockCallArg from './getMockCallArg';

export default function getLatestMockProps(mockFn: any, argIndex: number = 0): any {
    return getMockCallArg(mockFn, -1, argIndex);
}
