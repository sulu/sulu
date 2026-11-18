// @flow
import {render, screen} from '@testing-library/react';
import React from 'react';
import memoryFormStoreFactory from '../../../containers/Form/stores/memoryFormStoreFactory';
import WorkflowTransitionRequestDetailsCard from '../components/WorkflowTransitionRequestDetailsCard';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../containers/Form/stores/memoryFormStoreFactory', () => ({
    createFromFormKey: jest.fn(),
}));

const SCHEMA = {
    message: {label: 'Message to the reviewer', type: 'text_area'},
    publishAt: {label: 'Publish at', type: 'datetime'},
    flag: {label: 'Urgent', type: 'checkbox'},
};

function mockStore(loading: boolean = false, schema: Object = SCHEMA) {
    memoryFormStoreFactory.createFromFormKey.mockReturnValue({loading, schema});
}

test('loads the form of the request and shows the values in the order of the form', () => {
    mockStore();
    render(<WorkflowTransitionRequestDetailsCard data={{publishAt: '2026-11-25T10:00:00+00:00', message: 'Hello'}} />);

    expect(memoryFormStoreFactory.createFromFormKey).toHaveBeenCalledWith('workflow_transition_request_details');
    expect(screen.getAllByRole('term').map((element) => element.textContent))
        .toEqual(['Message to the reviewer', 'Publish at']);
});

test('formats a date and time in the locale of the browser', () => {
    mockStore();
    const value = '2026-11-25T10:00:00+00:00';
    const expected = new Intl.DateTimeFormat(undefined, {dateStyle: 'medium', timeStyle: 'short'})
        .format(new Date(value));

    render(<WorkflowTransitionRequestDetailsCard data={{publishAt: value}} />);

    expect(screen.getByText(expected)).toBeInTheDocument();
});

test('shows a date it cannot read as it is', () => {
    mockStore();

    render(<WorkflowTransitionRequestDetailsCard data={{publishAt: 'next week'}} />);

    expect(screen.getByText('next week')).toBeInTheDocument();
});

test('shows booleans and leaves out empty values and values of unknown fields', () => {
    mockStore();

    render(<WorkflowTransitionRequestDetailsCard data={{flag: true, message: '  ', other: 'not in the form'}} />);

    expect(screen.getAllByRole('definition').map((element) => element.textContent)).toEqual(['sulu_admin.yes']);
    expect(screen.queryByText('not in the form')).not.toBeInTheDocument();
});

test('leaves out a field that its condition hides, even when the form still holds a value', () => {
    mockStore(false, {
        publishAutomatically: {label: 'Publish automatically', type: 'checkbox'},
        publishAt: {label: 'Publish at', type: 'datetime', visibleCondition: 'publishAutomatically == true'},
    });

    const data = {publishAutomatically: false, publishAt: '2026-11-25T10:00:00+00:00'};
    render(<WorkflowTransitionRequestDetailsCard data={data} />);

    expect(screen.queryByText('Publish at')).not.toBeInTheDocument();
    expect(screen.getByText('Publish automatically')).toBeInTheDocument();
});

test('renders nothing while the form loads', () => {
    mockStore(true);

    const {container} = render(<WorkflowTransitionRequestDetailsCard data={{message: 'Hello'}} />);

    expect(container).toBeEmptyDOMElement();
});

test('renders nothing when no field has a value', () => {
    mockStore();

    const {container} = render(<WorkflowTransitionRequestDetailsCard data={{}} />);

    expect(container).toBeEmptyDOMElement();
});
