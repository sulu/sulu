// @flow
import {render} from '@testing-library/react';
import React from 'react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import SegmentSelect from '../../fields/SegmentSelect';
import SegmentSelectContainer from '../../../SegmentSelect';

jest.mock('../../../SegmentSelect', () => jest.fn(() => null));

beforeEach(() => {
    jest.clearAllMocks();
});

function createFormInspector(webspace: ?string = undefined) {
    return ({
        metadataOptions: webspace ? {webspace} : undefined,
    }: any);
}

test('Pass correct props to SegmentSelect', () => {
    render(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector('sulu_io')}
            value={{}}
        />
    );

    const [segmentSelectProps] = (SegmentSelectContainer: any).mock.calls[0];
    expect(segmentSelectProps.disabled).toEqual(true);
    expect(segmentSelectProps.value).toEqual({});
    expect(segmentSelectProps.webspace).toEqual('sulu_io');
});

test('Call onChange and onBlur if the value is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();

    render(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={createFormInspector()}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{
                'webspace-1': 's',
            }}
        />
    );

    const [segmentSelectProps] = (SegmentSelectContainer: any).mock.calls[0];
    segmentSelectProps.onChange({
        'webspace-1': 's',
        'webspace-3': 'a',
    });

    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
    expect(finishSpy).toBeCalledWith();
});
