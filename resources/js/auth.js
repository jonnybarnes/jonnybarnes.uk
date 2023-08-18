class Auth {
  constructor() {}

  async createCredentials() {
    const publicKeyCredentialCreationOptions = {
      challenge: Uint8Array.from(
        'randomStringFromServer',
        c => c.charCodeAt(0)
      ),
      rp: {
        id: 'jonnybarnes.localhost',
        name: 'JB',
      },
      user: {
        id: Uint8Array.from(
          'UZSL85T9AFC',
          c => c.charCodeAt(0)
        ),
        name: 'jonny@jonnybarnes.uk',
        displayName: 'Jonny',
      },
      pubKeyCredParams: [{alg: -7, type: 'public-key'}],
      // authenticatorSelection: {
      //   authenticatorAttachment: 'cross-platform',
      // },
      timeout: 60000,
      attestation: 'direct'
    };

    return await navigator.credentials.create({
      publicKey: publicKeyCredentialCreationOptions
    });
  }
}

export { Auth };
