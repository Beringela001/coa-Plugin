// Explicit review fixtures, transcribed from the original PDFs on 2026-09-08.
// These are not a database migration and must never override production records.
export const product = {id:865, url:'https://pepselect.com/product/glp3-r30/', compound:'retatrutide-30mg'};
const base='https://pepselect.com/testing/retatrutide-30mg/';
export const records = {
 past:{batch:'ND_R30_060326', state:'Past', compound:product.compound, url:base+'nd_r30_060326/', lab:'ILS Labs', date:'June 25, 2026', dateLabel:'Analysis date on certificate', reference:'COA-2026-IGZ9J2', purity:'97.62%', content:'30.45 mg', photo:'past-vial.webp', cap:'Pink (saved record)', crimp:'Silver', pdf:'https://pepselect.com/wp-content/uploads/2026/07/pep-select-ND-R30-060326-1OuLv5-1.pdf', source:'https://portal.ils-lab.com/verify/fDbRWJH7zVLY0xUE', pages:2,
 rows:[
  ['Identity (HPLC-RTM)','Confirmed','Pass','Chromatographic retention-time comparison with a reference standard. Specification: Retatrutide.'],
  ['Peptide purity','97.62%','Pass','RP-HPLC area normalization at 214 nm. Specification: ≥95.0%. The lab defines this relative to peptide-related peaks; non-peptide process impurities are excluded.'],
  ['Net peptide content','30.45 mg','Reported','Specification: Report Only. Original status: N/A. A quantity result, separate from purity. The certificate does not state an average or sample count.'],
  ['Heavy metals (ICP-MS)','Five analytes not detected','Pass','Arsenic: NMT 1.5 ppm; cadmium: NMT 0.5 ppm; chromium: NMT 10 ppm; mercury: NMT 1.5 ppm; lead: NMT 1 ppm. Each result: Not Detected, PASS. USP <233> methodology; internal laboratory research-material screening limits.'],
  ['Sterility Testing (PCR)','No Growth','Pass','The original laboratory heading is retained. Its row is Sterility (PCR), specification No Growth. Do not relabel this as a culture-based test.'],
  ['Endotoxin (USP <85>)','NMT 0.05 EU/mL','Reported','NMT means not more than. The certificate labels this Reported, not Pass. Specification: Report Result. Kinetic turbidimetric method. The lab notes that acceptable limits vary by product type and matrix.'],
  ['Fentanyl screen','Not Detected','Pass','Immunoassay, 50 ng/mL cutoff, as stated in this ILS certificate.']
 ],
 discrepancies:['The site currently shows June 24. The original certificate gives June 25 for analysis and issue.','The site labels endotoxin 0.05 EU/mL and Pass. The original says NMT 0.05 EU/mL and Reported.','The site records Average Net Content and 1 vial tested. This PDF states Net Peptide Content and does not substantiate averaging or that sample count.','The site gallery has three images; the attached PDF has two pages. Confirm the provenance of the third image.'],
 notes:'Existing report notes remain stored: Full QC Panel; lyophilized sample; 3 mL vial; HPLC retention-time identity; RP-HPLC at 214 nm; issued June 25, 2026; two certificate pages. Certificate version stored: 1.'},
 current:{batch:'RT3026233GX', state:'Current', compound:product.compound, url:base+'rt3026233gx/', lab:'Freedom Diagnostics Testing', date:'August 31, 2026', dateLabel:'Reported date on certificate', reference:'2608270076', purity:'99.86%', content:'32.78 mg', photo:'current-vial.png', cap:'Clear Yellow (saved record)', crimp:'Silver', pdf:'https://pepselect.com/wp-content/uploads/2026/08/PepS2608270076.pdf', source:'https://coas.freedomdiagnosticstesting.com/PepS2608270076.pdf', pages:1,
 rows:[
  ['Identity (LC-MS)','Confirmed','Reported','Sample summary: Confirmed. Analytical result: GLP RT. The certificate identifies the product as Retatrutide 30mg.'],
  ['Purity (HPLC-UV)','99.86%','Reported','The analytical row names HPLC-UV. A separate method line describes HPLC with UV detection coupled with LC-MS; the footer uses LCMS/MS wording. Numeric purity specification: not stated in this report.'],
  ['Net content','32.78 mg','Reported','The certificate states Net Content. A separate quantity method, average, vial count, and specification are not stated in this report.'],
  ['Elemental impurities (ICP-MS)','Four analytes within lab limits','Pass','Arsenic, cadmium, lead, and mercury below reporting/specification limits. Numeric limits are not stated in this report. Chromium is not listed.'],
  ['Microbial Analysis (PCR)','No Detectable Microbial DNA','Pass','Validated PCR-based assay targeting common microbial contaminants. This is the laboratory’s microbial screening statement; it is not relabeled as a different sterility method.'],
  ['Endotoxin (USP <85>)','Two replicates: Pass','Pass','Limulus Amebocyte Lysate assay. Assay sensitivity: ≤0.05 EU/mL. Sensitivity is a method characteristic, not a measured endotoxin concentration.'],
  ['Fentanyl','No Fentanyl Detected','Reported','Method and cutoff: not stated in this report. The ILS report’s immunoassay and 50 ng/mL cutoff must not be copied here.']
 ],
 discrepancies:['The site calls August 27 the test/report date. This PDF says Received August 27 and Reported August 31. An analysis date is not separately stated.','The site records Average Net Content and 3 vials tested. This PDF does not substantiate averaging or that sample count.','The site supplies Immunoassay / 50 ng/mL for fentanyl automatically. Neither is stated in this report.','The site groups the PCR microbial result under Sterility and combines endotoxin method/sensitivity in a result field. The proposed labels preserve these distinctions.'],
 notes:'Saved release decision: Approved. Current flag: Current. Saved vial photo/cap/crimp remain available. No certificate version is displayed in the current public record.'}
};
export function currentDestination(record, candidates=Object.values(records), mappedProduct=product) {
 const matches=candidates.filter(x=>x.compound===record.compound && x.state==='Current');
 if(matches.length!==1 || mappedProduct.compound!==record.compound || !mappedProduct.id) return null;
 return {report:matches[0].url, product:mappedProduct.url, batch:matches[0].batch};
}
