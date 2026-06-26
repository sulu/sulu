// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import metadataStore from '../stores/metadataStore';
import GhostDialog from '../GhostDialog';
import SingleSelect from '../fields/SingleSelect';
import Input from '../fields/Input';
import fieldRegistry from '../registries/fieldRegistry';

fieldRegistry.add('single_select', SingleSelect);
fieldRegistry.add('text_line', Input);

const FORM = {
    locale: {
        label: 'Sprache wählen',
        disabledCondition: null,
        visibleCondition: null,
        description: '',
        type: 'single_select',
        colSpan: 6,
        options: {
            default_value: {
                name: 'default_value',
                type: null,
                value: 'de',
                title: null,
                placeholder: null,
                infoText: null,
            },
            values: {
                name: 'values',
                type: 'collection',
                value: [
                    {
                        name: 'de',
                        type: null,
                        value: 'de',
                        title: 'de',
                        placeholder: null,
                        infoText: null,
                    },
                    {
                        name: 'en',
                        type: null,
                        value: 'en',
                        title: 'en',
                        placeholder: null,
                        infoText: null,
                    },
                ],
                title: null,
                placeholder: null,
                infoText: null,
            },
        },
        types: [],
        defaultType: null,
        required: true,
        spaceAfter: null,
        minOccurs: null,
        maxOccurs: null,
        onInvalid: null,
        tags: [],
    },
};

jest.mock('../../../utils/Translator');

jest.mock('../stores/metadataStore', () => ({
    getSchema: jest.fn(),
    getJsonSchema: jest.fn(),
}));

beforeEach(() => {
    jest.clearAllMocks();
    metadataStore.getSchema.mockReturnValue(Promise.resolve(FORM));
    metadataStore.getJsonSchema.mockReturnValue(Promise.resolve({}));
});

afterEach(() => {
    if (document.body) {
        document.body.innerHTML = '';
    }
});

test('Should render a Dialog', async() => {
    render(<GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={jest.fn()} open={true} />);

    expect(screen.getByTestId('backdrop')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.ghost_dialog_title')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.ghost_dialog_description')).toBeInTheDocument();
    expect(await screen.findByText('Sprache wählen *')).toBeInTheDocument();
    expect(screen.getByText('de')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.yes'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'sulu_admin.no'})).toBeInTheDocument();
});

test('Should call onCancel callback if user chooses not to copy content', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();

    render(<GhostDialog locales={['en', 'de']} onCancel={cancelSpy} onConfirm={jest.fn()} open={true} />);

    await user.click(screen.getByRole('button', {name: 'sulu_admin.no'}));

    expect(cancelSpy).toHaveBeenCalledWith();
});

test('Should call onConfirm callback with chosen locale if user chooses to copy content', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();

    render(<GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />);

    expect(await screen.findByText('Sprache wählen *')).toBeInTheDocument();
    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('en'));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.yes'}));

    expect(confirmSpy).toHaveBeenCalledWith('en', {});
});

test('Should call onConfirm callback with chosen locale if user chooses to copy content (with additional fields)', async() => { // eslint-disable-line max-len
    const user = userEvent.setup();
    const formMetadata = {
        ...FORM,
        title: {
            label: 'Test',
            disabledCondition: null,
            visibleCondition: null,
            description: '',
            type: 'text_line',
            colSpan: 6,
        },
    };
    metadataStore.getSchema.mockReturnValue(Promise.resolve(formMetadata));

    const confirmSpy = jest.fn();
    render(<GhostDialog locales={['en', 'de']} onCancel={jest.fn()} onConfirm={confirmSpy} open={true} />);

    expect(await screen.findByText('Sprache wählen *')).toBeInTheDocument();
    await user.type(screen.getByLabelText('Test'), 'Test 123');
    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('en'));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.yes'}));

    expect(confirmSpy).toHaveBeenCalledWith('en', {
        title: 'Test 123',
    });
});
