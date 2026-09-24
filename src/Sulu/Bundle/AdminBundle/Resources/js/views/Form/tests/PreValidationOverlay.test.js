// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import PreValidationOverlay from '../components/PreValidationOverlay';

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key, params) => (params ? key + ':' + JSON.stringify(params) : key)),
}));

// The shape the server sends: a translated message carries a key, raw text is what no key can express.
const RESULTS = [
    {key: 'seo_required', messages: [], passed: true},
    {
        key: 'excerpt_required',
        messages: [
            {key: null, parameters: {}, text: 'Excerpt fields are still missing: title.'},
            {
                key: 'sulu_content.workflow_transition_request.seo_required.missing',
                parameters: {fields: 'title'},
                text: null,
            },
        ],
        passed: false,
    },
];

test('Render one row per check, passed ones included', () => {
    render(<PreValidationOverlay onClose={jest.fn()} open={true} results={RESULTS} />);

    // no label configured for the key in this mock, so the row falls back to the key itself
    expect(screen.getByText('seo_required')).toBeInTheDocument();
    expect(screen.getByText('excerpt_required')).toBeInTheDocument();
    expect(screen.getByText('sulu_content.workflow_transition_request.pre_validator_passed')).toBeInTheDocument();
    expect(screen.getByText(
        'Excerpt fields are still missing: title. '
        + 'sulu_content.workflow_transition_request.seo_required.missing:{"fields":"title"}'
    )).toBeInTheDocument();
});

test('Count only the checks that passed', () => {
    render(<PreValidationOverlay onClose={jest.fn()} open={true} results={RESULTS} />);

    expect(screen.getByText(
        'sulu_content.workflow_transition_request.n_of_m_checks_passed:{"passed":1,"total":2}'
    )).toBeInTheDocument();
});

test('Render nothing while closed', () => {
    render(<PreValidationOverlay onClose={jest.fn()} open={false} results={RESULTS} />);

    expect(screen.queryByText(/Excerpt fields are still missing/)).not.toBeInTheDocument();
});

test('Call onClose when confirmed', async() => {
    const closeSpy = jest.fn();

    render(<PreValidationOverlay onClose={closeSpy} open={true} results={RESULTS} />);

    await userEvent.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

    expect(closeSpy).toHaveBeenCalled();
});
