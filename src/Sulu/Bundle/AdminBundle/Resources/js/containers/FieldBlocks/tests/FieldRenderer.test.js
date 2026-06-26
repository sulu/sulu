// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router from '../../../services/Router';
import FieldRenderer from '../FieldRenderer';
import {FormInspector, ResourceFormStore} from '../../Form';
import ResourceStore from '../../../stores/ResourceStore';

let mockRendererProps: Object = {};

const mockReact = require('react');

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../../Form', () => ({
    FormInspector: jest.fn(),
    ResourceFormStore: jest.fn(),
    Renderer: jest.fn((props) => {
        mockRendererProps = props;

        return mockReact.createElement(
            'div',
            {'data-testid': 'renderer'},
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'change',
                    onClick: () => props.onChange('test', 'value'),
                    type: 'button',
                },
                'Change'
            ),
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'change-with-context',
                    onClick: () => props.onChange('alignment', 'left', {isDefaultValue: true}),
                    type: 'button',
                },
                'Change with context'
            ),
            mockReact.createElement(
                'button',
                {
                    'aria-label': 'finish',
                    onClick: () => props.onFieldFinish && props.onFieldFinish(),
                    type: 'button',
                },
                'Finish'
            )
        );
    }),
}));

jest.mock('../../../stores/ResourceStore', () => jest.fn());

beforeEach(() => {
    jest.clearAllMocks();
    mockRendererProps = {};
});

function createFormInspector() {
    return new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'));
}

function renderFieldRenderer(props: Object = {}) {
    return render(
        <FieldRenderer
            data={{}}
            dataPath=""
            formInspector={createFormInspector()}
            index={2}
            onChange={jest.fn()}
            onFieldFinish={jest.fn()}
            onSuccess={jest.fn()}
            router={undefined}
            schema={{}}
            schemaPath=""
            value={{}}
            {...props}
        />
    );
}

test('Should pass props correctly to Renderer', () => {
    const fieldFinishSpy = jest.fn();
    const successSpy = jest.fn();

    const value = {
        title: 'Test',
    };

    const data = {
        content: 'test',
        block: value,
    };

    const errors = {
        content: {
            keyword: 'minLength',
            parameters: {},
        },
    };
    const schema = {
        text: {label: 'Label', type: 'text_line', visible: true},
    };
    const formInspector = createFormInspector();
    const router = new Router();

    renderFieldRenderer({
        data,
        dataPath: '/block/0/test',
        errors,
        formInspector,
        index: 1,
        onFieldFinish: fieldFinishSpy,
        onSuccess: successSpy,
        router,
        schema,
        schemaPath: '/test',
        value,
    });

    expect(mockRendererProps).toEqual(expect.objectContaining({
        data,
        dataPath: '/block/0/test',
        errors,
        formInspector,
        onFieldFinish: fieldFinishSpy,
        onSuccess: successSpy,
        router,
        schema,
        schemaPath: '/test',
        showAllErrors: false,
        value,
    }));
});

test('Should pass showAllErrors prop to Renderer', () => {
    renderFieldRenderer({showAllErrors: true});

    expect(mockRendererProps.showAllErrors).toEqual(true);
});

test('Should call onChange callback with correct index', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    renderFieldRenderer({index: 2, onChange: changeSpy});

    await user.click(screen.getByLabelText('change'));

    expect(changeSpy).toHaveBeenCalledWith(2, 'test', 'value', undefined);
});

test('Should pass context through onChange callback', async() => {
    const user = userEvent.setup();
    const changeSpy = jest.fn();
    renderFieldRenderer({index: 0, onChange: changeSpy});

    await user.click(screen.getByLabelText('change-with-context'));

    expect(changeSpy).toHaveBeenCalledWith(0, 'alignment', 'left', {isDefaultValue: true});
});

test('Should call onFieldFinish when some subfield finishes editing', async() => {
    const user = userEvent.setup();
    const fieldFinishSpy = jest.fn();
    renderFieldRenderer({onFieldFinish: fieldFinishSpy});

    await user.click(screen.getByLabelText('finish'));

    expect(fieldFinishSpy).toHaveBeenCalledWith();
});
