// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import SegmentSelectContainer from '../../../SegmentSelect';
import SegmentSelect from '../../fields/SegmentSelect';

jest.mock('../../../SegmentSelect', () => jest.fn(() => null));

test('Pass correct props to SegmentSelect', () => {
    const formInspector: any = {
        metadataOptions: {
            webspace: 'sulu_io',
        },
    };

    render(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            value={{}}
        />
    );

    const segmentSelectContainerProps: any = getLatestMockProps((SegmentSelectContainer: any));
    expect(segmentSelectContainerProps.disabled).toEqual(true);
    expect(segmentSelectContainerProps.value).toEqual({});
    expect(segmentSelectContainerProps.webspace).toEqual('sulu_io');
});

test('Call onChange and onBlur if the value is changed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const formInspector: any = {
        metadataOptions: {},
    };

    render(
        <SegmentSelect
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            value={{
                'webspace-1': 's',
            }}
        />
    );

    const segmentSelectContainerProps: any = getLatestMockProps((SegmentSelectContainer: any));
    segmentSelectContainerProps.onChange({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
    expect(changeSpy).toBeCalledWith({
        'webspace-1': 's',
        'webspace-3': 'a',
    });
    expect(finishSpy).toBeCalledWith();
});
