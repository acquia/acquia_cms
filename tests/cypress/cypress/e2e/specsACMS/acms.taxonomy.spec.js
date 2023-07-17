// TC-## are the qTest test case id's <reference types="cypress" />.
const taxonomy = require("../../pages/Taxonomy")
// TC-94.
describe("Taxonomy adding new Vocabulary and new Term to it",()=>{
  context("Taxonomy - Add new Vocabulary",()=>{
      it("Add vocabulary, save it in Taxonomy and validate",()=>{
          taxonomy.addVocabulary()
          taxonomy.validateAddedVocabulary()
          taxonomy.deleteVocab()
      })
  })

  // TC-95.
  context("Taxonomy - Add term to newly added Vocabulary",()=>{
      it("Adding and validating term to newly added Vocabulary",()=>{
          taxonomy.termToVocab()
          taxonomy.validateAddedTerm()
          taxonomy.deleteTerm()
          taxonomy.deleteVocab()
      })

  })
})
