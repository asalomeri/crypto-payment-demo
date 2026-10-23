# NFT Metadata & GitHub Linking Guide

This guide shows how to connect a Solana NFT to your GitHub profile or project in a professional way.

## Metadata fields that identify you and your project

| Field | What it does | Tip |
|---|---|---|
| `name` | The NFT title shown in wallets and marketplaces. | Keep it short and use the same name everywhere. |
| `description` | Text explaining what the NFT is. | Mention the creator/project and say where to find it on GitHub. |
| `image` | URL of the main image. | Use a permanent URL (see best practices). |
| `external_url` | A link shown on marketplaces as "website". | **Put your GitHub profile or project URL here.** |
| `attributes` | List of traits (`trait_type` / `value`). | Add identity traits such as `creator`, `project`, `network`. |
| `properties.files` | List of files attached to the NFT (`uri` and `type`). | Include the main image and any other files, with correct MIME types. |

## Copy-paste example

Replace the placeholder URLs with your own.

```json
{
  "name": "Minty Camera Roll",
  "symbol": "MCR",
  "description": "Original digital collectible by asalomeri. Source and project details: https://github.com/asalomeri",
  "image": "https://your-domain.com/assets/minty-camera-roll.png",
  "external_url": "https://github.com/asalomeri",
  "attributes": [
    { "trait_type": "creator", "value": "asalomeri" },
    { "trait_type": "project", "value": "crypto-payment-demo" },
    { "trait_type": "network", "value": "Solana" }
  ],
  "properties": {
    "category": "image",
    "files": [
      {
        "uri": "https://your-domain.com/assets/minty-camera-roll.png",
        "type": "image/png"
      }
    ]
  }
}
```

Steps:

1. Save the JSON as a file (for example `metadata.json`).
2. Upload the image and the JSON to permanent storage (Arweave, IPFS with pinning, etc.).
3. Use the JSON's URL as the NFT's `uri` when minting (or when updating metadata).
4. Check the JSON with a validator such as <https://jsonlint.com> before uploading.

## Linking the NFT to a GitHub project

- Add a section about the NFT to your repository `README.md` (name, network, mint address, Solscan link).
- Link back to the repository from `external_url` and `description`.
- Use the same name, logo and description in the repo, the metadata and any project website.

## Best practices

- **Consistent branding:** same name, description, image style and links across metadata, GitHub and any website.
- **Permanent hosting:** don't use temporary or personal servers for the image or JSON. Use Arweave or pinned IPFS so links don't break.
- **Trust signals:** use a verified creator and a verified collection where possible, keep an up-to-date README, and publish the mint address in your repo so people can cross-check it.
- **Security:** never share your seed phrase with anyone claiming to "verify" your NFT.

## Limitations for already-minted NFTs

- Metadata can only be changed by the **update authority**. The NFT **owner** is not automatically the update authority.
- NFTs minted through a service (for example a wallet feature or Crossmint) often have the service as update authority, so you may not be able to edit them.
- Creators marked `verified: 0` can't be verified without the creator's key.
- Compressed NFTs (cNFTs) follow the same rule: only the authority can update them.
- If you don't control the update authority, you can still: publish a project page or README that references the NFT's mint address, or mint a new NFT with your own metadata.
