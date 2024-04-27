function event_trade(e)
	local expansion_flag = eq.get_current_expansion();
	local item_lib = require("items");
	
	if(expansion_flag >= 4.0 and item_lib.check_turn_in(e.self, e.trade, {item1 = 27498}, 0)) then
		e.self:Say("Oh my I am very thankful for these rations. I have been very hungry. Please send our teacher my regards and this report. Good luck!");
		-- Confirmed Live Factions and Experience
		e.other:Faction(e.self,341,10); -- Priest of Life
		e.other:Faction(e.self,280,3); -- Knight of Thunder
		e.other:Faction(e.self,262,5); -- Guards of Qeynos
		e.other:Faction(e.self,221,-2); -- Bloodsabers
		e.other:Faction(e.self,219,1); -- Antonius Bayle
		e.other:QuestReward(e.self,{itemid = 27420,exp = 500});
	end
	item_lib.return_items(e.self, e.other, e.trade)
end
