// @flow

type Email = {|
    email: ?string,
|};

type Fax = {|
    fax: ?string,
|};

type Phone = {|
    phone: ?string,
|};

type SocialMedia = {|
    username: ?string,
|};

type Website = {|
    website: ?string,
|};

export type ContactDetailsValue = {|
    emails: Array<Email>,
    faxes: Array<Fax>,
    phones: Array<Phone>,
    socialMedia: Array<SocialMedia>,
    websites: Array<Website>,
|};
