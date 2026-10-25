export default function Head({ title = "DAV Public School Dugda", description = "Complete School Management System for DAV Public School, Dugda — Admin, Staff, Teachers, Parents, Students & Public Website." }: { title?: string; description?: string }) {
  return (
    <>
      <title>{title}</title>
      <meta name="description" content={description} />
      <meta property="og:title" content={title} />
      <meta property="og:description" content={description} />
      <meta property="og:type" content="website" />
      <meta property="og:url" content="https://davdugda-sms.app" />
      <meta name="twitter:card" content="summary" />
      <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
    </>
  );
}
